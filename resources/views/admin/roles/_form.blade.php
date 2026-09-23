@php
    $isLocked = ($role?->name === 'Super Admin');
    $initialSelected = collect(old('permissions', $selected ?? []))->values()->all();
    $initialName = old('name', $role?->name);
    $allPermissions = \App\Support\PermissionCatalog::all();
@endphp

<div
    class="rbac-page"
    x-data="rolePermissionForm({
        name: @js($initialName),
        selected: @js($initialSelected),
        presets: @js($presets),
        descriptions: @js($presetDescriptions),
        allPermissions: @js($allPermissions),
        locked: @js($isLocked),
    })"
>
    @if ($errors->any())
        <div class="rbac-alert rbac-alert--error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rbac-toolbar">
        <div class="rbac-field rbac-field--grow">
            <label class="label">Role name</label>
            <input
                class="input"
                name="name"
                x-model="name"
                @disabled($isLocked)
                required
                placeholder="e.g. Regional Sales Lead"
            >
            @if ($isLocked)
                <input type="hidden" name="name" value="Super Admin">
                <p class="muted rbac-hint">Super Admin always retains full system access.</p>
            @endif
        </div>

        @unless ($isLocked)
            <div class="rbac-field rbac-field--preset">
                <label class="label">Start from role template</label>
                <select class="select" x-model="activePreset" @change="applyPreset($event.target.value)">
                    <option value="">Choose a recommended set…</option>
                    <template x-for="(perms, roleName) in presets" :key="roleName">
                        <option :value="roleName" x-text="roleName"></option>
                    </template>
                </select>
                <p class="muted rbac-hint" x-show="activePreset" x-text="descriptions[activePreset] || ''"></p>
            </div>
        @endunless

        <div class="rbac-summary">
            <span class="rbac-summary__count" x-text="selected.length + ' permissions'"></span>
            @unless ($isLocked)
                <button type="button" class="btn btn-ghost rbac-summary__btn" @click="selectAll()">Select all</button>
                <button type="button" class="btn btn-ghost rbac-summary__btn" @click="clearAll()">Clear</button>
            @endunless
        </div>
    </div>

    @unless ($isLocked)
        <div class="rbac-templates">
            <div class="rbac-templates__label">Quick templates — click to load the right access</div>
            <div class="rbac-templates__grid">
                @foreach ($presets as $presetName => $perms)
                    <button
                        type="button"
                        class="rbac-template"
                        :class="{ 'is-active': activePreset === @js($presetName) }"
                        @click="applyPreset(@js($presetName))"
                    >
                        <strong>{{ $presetName }}</strong>
                        <span>{{ $presetDescriptions[$presetName] ?? '' }}</span>
                        <em>{{ count($perms) }} access rights</em>
                    </button>
                @endforeach
            </div>
        </div>
    @endunless

    <div class="rbac-modules">
        @foreach ($modules as $module => $actions)
            @php
                $modulePerms = collect($actions)->keys()->map(fn ($a) => "{$module}.{$a}")->values()->all();
            @endphp
            <section
                class="rbac-module"
                :class="{ 'is-partial': moduleState(@js($modulePerms)) === 'partial', 'is-on': moduleState(@js($modulePerms)) === 'on' }"
            >
                <header class="rbac-module__head">
                    <label class="rbac-module__title">
                        @unless ($isLocked)
                            <input
                                type="checkbox"
                                :checked="moduleState(@js($modulePerms)) === 'on'"
                                :indeterminate.prop="moduleState(@js($modulePerms)) === 'partial'"
                                @change="toggleModule(@js($modulePerms), $event.target.checked)"
                            >
                        @endunless
                        <span>{{ str_replace('_', ' ', $module) }}</span>
                    </label>
                    <span class="rbac-module__meta" x-text="moduleSelectedCount(@js($modulePerms)) + '/' + {{ count($modulePerms) }}"></span>
                </header>
                <div class="rbac-module__body">
                    @foreach ($actions as $action => $label)
                        @php $perm = "{$module}.{$action}"; @endphp
                        <label class="rbac-perm" :class="{ 'is-checked': isSelected(@js($perm)) }">
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $perm }}"
                                :checked="isSelected(@js($perm))"
                                @change="toggle(@js($perm), $event.target.checked)"
                                @disabled($isLocked)
                            >
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>

@once
    @push('scripts')
        <script>
            function rolePermissionForm(config) {
                return {
                    name: config.name || '',
                    selected: Array.isArray(config.selected) ? [...config.selected] : [],
                    presets: config.presets || {},
                    descriptions: config.descriptions || {},
                    allPermissions: Array.isArray(config.allPermissions) ? config.allPermissions : [],
                    locked: !!config.locked,
                    activePreset: '',

                    isSelected(perm) {
                        return this.selected.includes(perm);
                    },

                    toggle(perm, on) {
                        if (this.locked) return;
                        if (on && !this.selected.includes(perm)) {
                            this.selected.push(perm);
                        } else if (!on) {
                            this.selected = this.selected.filter((p) => p !== perm);
                        }
                        this.activePreset = '';
                    },

                    toggleModule(perms, on) {
                        if (this.locked) return;
                        if (on) {
                            perms.forEach((p) => {
                                if (!this.selected.includes(p)) this.selected.push(p);
                            });
                        } else {
                            this.selected = this.selected.filter((p) => !perms.includes(p));
                        }
                        this.activePreset = '';
                    },

                    moduleSelectedCount(perms) {
                        return perms.filter((p) => this.selected.includes(p)).length;
                    },

                    moduleState(perms) {
                        const n = this.moduleSelectedCount(perms);
                        if (n === 0) return 'off';
                        if (n === perms.length) return 'on';
                        return 'partial';
                    },

                    applyPreset(roleName) {
                        if (this.locked || !roleName || !this.presets[roleName]) return;
                        this.activePreset = roleName;
                        this.selected = [...this.presets[roleName]];
                        if (!this.name || Object.prototype.hasOwnProperty.call(this.presets, this.name)) {
                            this.name = roleName;
                        }
                    },

                    selectAll() {
                        if (this.locked) return;
                        this.selected = [...this.allPermissions];
                        this.activePreset = '';
                    },

                    clearAll() {
                        if (this.locked) return;
                        this.selected = [];
                        this.activePreset = '';
                    },
                };
            }
        </script>
    @endpush
@endonce
