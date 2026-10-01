@if ($loginsToCreate->isNotEmpty())
    <section class="pl-todo">
        <div class="pl-todo-head">
            <span class="pl-todo-ico"><i data-lucide="key-round"></i></span>
            <div>
                <strong>{{ $loginsToCreate->count() === 1 ? '1 shop was accepted' : $loginsToCreate->count().' shops were accepted' }} — create the owner's login</strong>
                <span>Set a login email and password with the owner, then check it works before you leave.</span>
            </div>
        </div>
        <div class="pl-todo-list">
            @foreach ($loginsToCreate as $todo)
                <a class="pl-todo-item" href="{{ route('field.partners.login', $todo) }}">
                    <span class="pl-todo-name">{{ $todo->shop?->name ?? $todo->business_name }}<small>{{ collect([$todo->shop?->code, $todo->reviewed_at?->diffForHumans()])->filter()->implode(' · ') }}</small></span>
                    <span class="f-btn f-btn-primary f-btn-sm"><i data-lucide="user-plus"></i> Create login</span>
                </a>
            @endforeach
        </div>
    </section>
@endif
