@php
    $needsPhoto = $visit->needsPhotoEvidence();
    $leaveTo = request('to', $leaveRedirect ?? 'shops');
    $openLeave = request()->boolean('leave') || $errors->has('notes') || $errors->has('photo');
@endphp
<div
    x-data="{
        open: {{ $openLeave ? 'true' : 'false' }},
        preview: '',
        redirect: @js($leaveTo),
        pick(event) {
            const file = event.target.files?.[0];
            if (this.preview) URL.revokeObjectURL(this.preview);
            this.preview = file ? URL.createObjectURL(file) : '';
            this.$nextTick(() => window.lucide && lucide.createIcons());
        }
    }"
    @open-leave.window="open = true; if ($event.detail) redirect = $event.detail; $nextTick(() => document.getElementById('leave-notes')?.focus())"
>
    <div class="f-sheet-backdrop" x-show="open" x-cloak x-transition.opacity @click="open = false"></div>
    <form class="f-sheet" x-show="open" x-cloak x-transition method="post" action="{{ route('field.visit.leave', $visit) }}" enctype="multipart/form-data" @keydown.escape.window="open = false">
        @csrf
        <input type="hidden" name="redirect" :value="redirect">
        <div class="f-sheet-grip"></div>
        <h3>I'm leaving {{ $visit->shop?->name }}</h3>
        <p class="f-muted f-small" style="margin:0 0 12px">Adding a shop and visiting a shop are different. This visit needs a note@if ($needsPhoto) and a photo@endif.</p>

        @if ($visit->order_id)
            <input type="hidden" name="outcome" value="order_taken">
            <div class="f-chip green" style="margin-bottom:12px">Order already taken on this visit</div>
        @else
            <div class="f-choices">
                <label class="f-choice"><input type="radio" name="outcome" value="no_order" checked><span><i data-lucide="x-circle"></i>No order today</span></label>
                <label class="f-choice"><input type="radio" name="outcome" value="closed"><span><i data-lucide="lock"></i>Shop closed</span></label>
                <label class="f-choice"><input type="radio" name="outcome" value="follow_up"><span><i data-lucide="calendar-clock"></i>Follow up later</span></label>
            </div>
        @endif

        <label class="f-label" for="leave-notes">What did you talk about with the owner? <em class="as-req">*</em></label>
        <textarea id="leave-notes" class="f-textarea @error('notes') is-invalid @enderror" name="notes" rows="3" maxlength="1000" required placeholder="e.g. asked about charger stock, will order next week, owner was busy…">{{ old('notes', $visit->notes) }}</textarea>
        @error('notes')<div class="f-error">{{ $message }}</div>@enderror

        <label class="f-photo {{ $needsPhoto ? 'is-required' : '' }}">
            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" capture="environment" @change="pick($event)" {{ $needsPhoto ? 'required' : '' }}>
            <span class="f-photo-box" x-show="!preview">
                <i data-lucide="camera"></i>
                <b>{{ $needsPhoto ? 'Take a photo at this shop' : 'Photo of the visit (optional)' }}</b>
                <small>{{ $needsPhoto ? 'Required — this shop is on your assigned route.' : 'Office can see you were here.' }}</small>
            </span>
            <span class="f-photo-box has-img" x-show="preview" x-cloak>
                <img :src="preview" alt="Visit evidence">
                <small>Tap to retake</small>
            </span>
        </label>
        @error('photo')<div class="f-error">{{ $message }}</div>@enderror

        <button class="f-btn f-btn-primary f-btn-lg f-btn-block" style="margin-top:14px" type="submit"><i data-lucide="log-out"></i> Save visit &amp; leave</button>
        <button class="f-btn f-btn-ghost f-btn-block" style="margin-top:8px" type="button" @click="open = false">Stay at this shop</button>
    </form>
</div>
