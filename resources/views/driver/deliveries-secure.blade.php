@extends('driver.deliveries')

@section('script')
@parent
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input[name="customer_confirmed"]').forEach(function (input) {
        var field = input.closest('.drv-field');
        if (field) field.remove();
    });

    document.querySelectorAll('form.delivery-action-form').forEach(function (form) {
        var status = form.querySelector('input[name="status"]');
        if (!status || status.value !== 'DELIVERED') return;

        var cashInstruction = Array.from(form.querySelectorAll('div')).find(function (node) {
            return (node.textContent || '').indexOf('Encaisser') !== -1;
        });
        if (!cashInstruction || form.querySelector('[name="cash_collection_outcome"]')) return;

        var field = document.createElement('div');
        field.className = 'drv-field';
        field.innerHTML = ''
            + '<label>Résultat de l’encaissement cash</label>'
            + '<label style="display:flex;align-items:center;gap:8px;margin:6px 0;cursor:pointer;">'
            + '<input type="radio" name="cash_collection_outcome" value="collected" required style="width:auto;"> Espèces encaissées intégralement'
            + '</label>'
            + '<label style="display:flex;align-items:center;gap:8px;margin:6px 0;cursor:pointer;">'
            + '<input type="radio" name="cash_collection_outcome" value="collection_failed" required style="width:auto;"> Encaissement impossible ou incomplet'
            + '</label>';

        var actions = form.querySelector('.drv-form-actions');
        if (actions) form.insertBefore(field, actions);
        else form.appendChild(field);
    });
});
</script>
@endsection
