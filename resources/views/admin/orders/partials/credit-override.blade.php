@unless ($snapshot['credit_ok'])
    <p class="order-decide-hint">
        Over credit limit — needs {{ \App\Support\DemoData::taka($snapshot['credit_needed']) }},
        only {{ \App\Support\DemoData::taka($snapshot['credit_available']) }} free
        (limit {{ \App\Support\DemoData::taka($snapshot['credit_limit']) }}, owed {{ \App\Support\DemoData::taka($snapshot['credit_exposure']) }}).
    </p>
    @can('orders.credit_override')
        <label class="order-field" style="display:flex;gap:8px;align-items:center;font-size:13px">
            <input type="checkbox" name="credit_override" value="1" @checked(old('credit_override'))>
            <span>Override credit limit and approve anyway</span>
        </label>
    @else
        <p class="order-decide-hint">Request an advance, or ask an admin to approve with a credit override.</p>
    @endcan
@endunless
