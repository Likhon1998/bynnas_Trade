@extends('field.layouts.app')
@section('title', 'Visit')
@section('heading', $visit->shop?->name)
@section('content')
    <div class="list-card">
        <div class="muted" style="font-size:12px">Checked in {{ $visit->checked_in_at?->format('H:i') }} · {{ $visit->outcomeLabel() }}</div>
        <div style="font-weight:700;margin-top:4px">{{ $visit->shop?->owner_name }} · {{ $visit->shop?->phone }}</div>
        <div class="muted" style="font-size:12px">{{ $visit->shop?->address }}</div>
    </div>

    @if ($visit->order)
        <div class="flash-ok">Order {{ $visit->order->number }} already linked to this visit.</div>
        <a class="btn btn-primary btn-block" href="{{ route('field.orders.show', $visit->order) }}">View order</a>
    @elseif ($visit->isOpen())
        <form method="post" action="{{ route('field.visit.order', $visit) }}" x-data="{ items: {} }">
            @csrf
            <div style="font-weight:700;margin:8px 0 10px">Collect order</div>
            <div class="list-card" style="padding:8px 14px">
                @foreach ($products as $product)
                    @php $price = $product->priceForGroup($visit->shop?->priceGroup); @endphp
                    <div class="qty-row">
                        <div style="min-width:0;flex:1">
                            <div style="font-weight:600;font-size:13px">{{ $product->name }}</div>
                            <div class="muted" style="font-size:11px">{{ $product->sku }} · {{ \App\Support\DemoData::taka($price) }}</div>
                        </div>
                        <input type="hidden" name="items[{{ $loop->index }}][product_id]" value="{{ $product->id }}">
                        <input class="input" type="number" min="0" name="items[{{ $loop->index }}][quantity]" value="0" inputmode="numeric">
                    </div>
                @endforeach
            </div>
            <div class="field" style="margin:12px 0">
                <label class="label">Order notes</label>
                <textarea class="input" name="notes" rows="2" placeholder="Optional"></textarea>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Submit for Super Admin audit</button>
        </form>

        <form method="post" action="{{ route('field.visit.checkout', $visit) }}" style="margin-top:12px">
            @csrf
            <input type="hidden" name="outcome" value="no_order">
            <button class="btn btn-ghost btn-block" type="submit">Check out without order</button>
        </form>
    @else
        <div class="list-card muted">Visit closed · {{ $visit->outcomeLabel() }}</div>
    @endif
@endsection
