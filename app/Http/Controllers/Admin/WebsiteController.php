<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WebsiteController extends Controller
{
    public function __construct(private SiteContent $site, private AuditLogger $auditLogger) {}

    public function edit(Request $request)
    {
        abort_unless($request->user()->can('website.view'), 403);

        $tab = in_array($request->query('tab'), SiteContent::sections(), true) ? $request->query('tab') : 'general';

        return view('admin.website.edit', [
            'tab' => $tab,
            'schema' => SiteContent::schema(),
            'values' => $this->site->section($tab),
            'lastUpdated' => $this->site->lastUpdated($tab),
            'canManage' => $request->user()->can('website.manage'),
            'icons' => SiteContent::ICONS,
            'previewUrl' => $this->previewUrl($tab),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->can('website.manage'), 403);

        $section = (string) $request->input('section');
        abort_unless(in_array($section, SiteContent::sections(), true), 404);

        $fields = SiteContent::fields($section);
        $data = $request->validate($this->rules($fields), [], $this->attributeNames($fields));

        $images = collect($fields)->filter(fn ($f) => $f['type'] === 'image')->keys();
        foreach ($images as $key) {
            if ($request->hasFile($key)) {
                $data[$key] = $request->file($key);
            }
        }

        $before = $this->site->section($section);
        $this->site->save($section, $data, $request->user(), array_intersect((array) $request->input('remove_images', []), $images->all()));

        $changed = collect($this->site->section($section))
            ->filter(fn ($value, $key) => $value !== ($before[$key] ?? null))
            ->keys()
            ->all();
        $this->auditLogger->log('website', 'updated', 'Website '.SiteContent::schema()[$section]['label'].' updated', null, null, ['section' => $section, 'changed' => $changed], $request->user());

        return redirect()
            ->route('website.edit', ['tab' => $section])
            ->with('success', SiteContent::schema()[$section]['label'].' saved — the public site is updated.');
    }

    public function reset(Request $request)
    {
        abort_unless($request->user()->can('website.manage'), 403);

        $section = (string) $request->input('section');
        abort_unless(in_array($section, SiteContent::sections(), true), 404);

        $this->site->reset($section);
        $this->auditLogger->log('website', 'reset', 'Website '.SiteContent::schema()[$section]['label'].' reset to default', null, null, ['section' => $section], $request->user());

        return redirect()
            ->route('website.edit', ['tab' => $section])
            ->with('success', SiteContent::schema()[$section]['label'].' restored to the default wording.');
    }

    /** @return array<string, mixed> */
    private function rules(array $fields): array
    {
        $rules = ['remove_images' => ['nullable', 'array'], 'remove_images.*' => ['string']];

        foreach ($fields as $key => $field) {
            $max = $field['max'] ?? 255;
            $base = ! empty($field['required']) ? ['required'] : ['nullable'];

            match ($field['type']) {
                'email' => $rules[$key] = [...$base, 'email', "max:{$max}"],
                'url' => $rules[$key] = [...$base, 'url:http,https', "max:{$max}"],
                'toggle' => $rules[$key] = ['nullable', 'boolean'],
                'image' => $rules[$key] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
                'list' => [
                    $rules[$key] = ['nullable', 'array', 'max:'.($field['max_items'] ?? 10)],
                    $rules["{$key}.*"] = ['nullable', 'string', 'max:160'],
                ],
                'items' => [
                    $rules[$key] = ['nullable', 'array', 'max:'.($field['max_items'] ?? 10)],
                    $rules["{$key}.*.title"] = ['nullable', 'string', 'max:120'],
                    $rules["{$key}.*.text"] = ['nullable', 'string', 'max:400'],
                    $rules["{$key}.*.icon"] = ['nullable', Rule::in(array_keys(SiteContent::ICONS))],
                ],
                default => $rules[$key] = [...$base, 'string', "max:{$max}"],
            };
        }

        return $rules;
    }

    /** @return array<string, string> */
    private function attributeNames(array $fields): array
    {
        return collect($fields)->map(fn ($f) => strtolower($f['label']))->all();
    }

    private function previewUrl(string $tab): string
    {
        return match ($tab) {
            'about' => route('site.about'),
            'contact' => route('site.contact'),
            'partner' => route('site.partner'),
            default => route('site.home'),
        };
    }
}
