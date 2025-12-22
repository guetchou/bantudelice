<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Étiquette Colis - {{ $shipment->tracking_number }}</title>
    <style>
        body { font-family: sans-serif; margin: 20px; }
        .label-container { border: 2px solid #000; padding: 20px; width: 400px; margin: 0 auto; }
        .header { text-align: center; border-bottom: 1px solid #000; padding-bottom: 10px; }
        .tracking { font-size: 24px; font-weight: bold; margin: 15px 0; text-align: center; }
        .section { margin-top: 15px; }
        .section-title { font-weight: bold; text-decoration: underline; }
        .footer { margin-top: 20px; font-size: 12px; text-align: center; border-top: 1px solid #000; padding-top: 10px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: #fff; border: none; cursor: pointer;">Imprimer l'étiquette</button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: #fff; border: none; cursor: pointer;">Fermer</button>
    </div>

    <div class="label-container">
        <div class="header">
            <h2 style="margin:0;">BantuDelice 242</h2>
            <p style="margin:0;">Service de Livraison de Colis</p>
        </div>

        <div class="tracking">
            {{ $shipment->tracking_number }}
        </div>

        <div class="section">
            <div class="section-title">EXPÉDITEUR :</div>
            @php $p = $shipment->pickupAddress(); @endphp
            {{ $p->full_name }}<br>
            {{ $p->phone }}<br>
            {{ $p->address_line }}, {{ $p->district }}
        </div>

        <div class="section">
            <div class="section-title">DESTINATAIRE :</div>
            @php $d = $shipment->dropoffAddress(); @endphp
            {{ $d->full_name }}<br>
            {{ $d->phone }}<br>
            {{ $d->address_line }}, {{ $d->district }}
        </div>

        <div class="section" style="border-top: 1px dashed #ccc; padding-top: 10px;">
            <strong>Poids :</strong> {{ $shipment->weight_kg }} kg<br>
            <strong>Service :</strong> {{ strtoupper($shipment->service_level) }}<br>
            <strong>COD à collecter :</strong> <span style="font-size: 18px; font-weight: bold;">{{ number_format($shipment->cod_amount, 0, ',', ' ') }} FCFA</span>
        </div>

        <div class="footer">
            Merci de votre confiance !<br>
            www.bantudelice.cg
        </div>
    </div>

    <script>
        // Auto-print if needed
        // window.print();
    </script>
</body>
</html>

