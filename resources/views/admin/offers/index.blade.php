@extends('layouts.app')
@section('title', 'Product offers')
@section('content')
    <x-page-header title="Special offers" subtitle="Time-bound offer prices for salesmen and shopkeepers" />

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="card" style="padding:16px;margin-bottom:14px">
        <div style="font-weight:700;margin-bottom:12px">Add offer</div>
        <form method="post" action="{{ route('offers.store') }}" class="form-grid">
            @csrf
            <div class="field">
                <label class="label">Product</label>
                <select class="select" name="product_id" required>
                    <option value="">Select product</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected(old('product_id', request('product_id')) == $product->id)>{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label class="label">Title</label><input class="input" name="title" value="{{ old('title') }}" required></div>
            <div class="field"><label class="label">Offer price (৳)</label><input class="input" type="number" step="0.01" min="0.01" name="offer_price" value="{{ old('offer_price') }}" required></div>
            <div class="field"><label class="label">Starts</label><input class="input" type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"></div>
            <div class="field"><label class="label">Ends</label><input class="input" type="datetime-local" name="ends_at" value="{{ old('ends_at') }}"></div>
            <div class="field" style="align-self:end"><label><input type="checkbox" name="is_active" value="1" checked> Active</label></div>
            <div style="align-self:end"><button class="btn btn-primary" type="submit">Save offer</button></div>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Product</th><th>Title</th><th>Price</th><th>Validity</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($offers as $offer)
                        <tr>
                            <td>{{ $offer->product?->name }} <span class="muted">{{ $offer->product?->sku }}</span></td>
                            <td>{{ $offer->title }}</td>
                            <td>{{ \App\Support\DemoData::taka($offer->offer_price) }}</td>
                            <td class="muted" style="font-size:12px">
                                {{ $offer->starts_at?->format('d M Y') ?: 'Anytime' }}
                                → {{ $offer->ends_at?->format('d M Y') ?: 'Open' }}
                            </td>
                            <td>{{ $offer->isCurrentlyActive() ? 'Live' : ($offer->is_active ? 'Scheduled/expired' : 'Off') }}</td>
                            <td>
                                <form method="post" action="{{ route('offers.destroy', $offer) }}" onsubmit="return confirm('Deactivate this offer?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost btn-sm" type="submit">Deactivate</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted" style="padding:16px">No offers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:12px">{{ $offers->links() }}</div>
    </div>
@endsection
