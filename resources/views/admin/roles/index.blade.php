@extends('layouts.app')

@section('title', 'Roles')

@section('content')
    <x-page-header title="Roles" subtitle="Custom roles and permission sets" action="{{ route('roles.create') }}" action-label="Create role">
        <x-slot:description>Super Admin can create additional roles beyond the seeded system roles. Permissions stay granular by module.</x-slot:description>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;border-color:#bbf7d0;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Users</th>
                        <th>Permissions</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td style="font-weight:700">{{ $role->name }}</td>
                            <td>{{ $role->users_count }}</td>
                            <td>{{ $role->permissions_count }}</td>
                            <td style="white-space:nowrap">
                                @can('roles.edit')
                                    <a class="btn btn-ghost" href="{{ route('roles.edit', $role) }}" style="padding:6px 10px">Edit</a>
                                @endcan
                                @can('roles.delete')
                                    @if ($role->name !== 'Super Admin')
                                        <form action="{{ route('roles.destroy', $role) }}" method="post" style="display:inline" onsubmit="return confirm('Delete this role?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-ghost" type="submit" style="padding:6px 10px;color:#b91c1c">Delete</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
