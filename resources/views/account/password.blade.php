@extends($layout)
@section('title', 'Change password')
@section('heading', 'Change password')
@section('back', $portal === 'field' ? route('field.dashboard') : '')
@section('content')
    @php
        $isField = $portal === 'field';
        $inputClass = $isField ? 'f-input' : 'input';
    @endphp

    @unless ($isField)
        <x-page-header title="Change password" subtitle="Use at least 8 characters. Other signed-in devices will be signed out." />
        @if (session('success'))
            <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
        @endif
    @endunless

    <div class="{{ $isField ? 'f-panel' : 'card' }}" style="padding:20px;max-width:480px">
        @if ($errors->any())
            <div style="margin-bottom:14px;padding:10px 12px;border-radius:10px;background:#fef2f2;color:#b91c1c">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="post" action="{{ $action }}" style="display:grid;gap:14px">
            @csrf
            @method('PUT')
            <label style="display:grid;gap:6px">
                <span style="font-weight:600">Current password</span>
                <input class="{{ $inputClass }}" type="password" name="current_password" autocomplete="current-password" required>
            </label>
            <label style="display:grid;gap:6px">
                <span style="font-weight:600">New password</span>
                <input class="{{ $inputClass }}" type="password" name="password" autocomplete="new-password" minlength="8" required>
            </label>
            <label style="display:grid;gap:6px">
                <span style="font-weight:600">Confirm new password</span>
                <input class="{{ $inputClass }}" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required>
            </label>
            <div>
                <button type="submit" class="{{ $isField ? 'f-btn f-btn-primary' : 'btn btn-primary' }}">Update password</button>
            </div>
        </form>
    </div>
@endsection
