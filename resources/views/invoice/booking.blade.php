<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Booking Invoice - {{ $booking->booking_reference }}</title>

    <style>
        body { 
            margin:0; padding:0; 
           
            color:#243240; 
            font-size:13px;
        }

        .wrapper {
            padding: 15px 20px; /* ↓ smaller overall padding */
        }

        .border-bottom {
            border-bottom: 4px solid #dfe9f2; /* ↓ thinner */
            padding-bottom: 6px; /* ↓ smaller */
        }

        .hotel-name {
            font-size: 26px;   /* ↓ smaller font */
            font-weight: 700;
            color:#195089;
        }

        .section-title {
            color:#195089;
            font-weight:700;
            font-size:14px;
            margin-bottom:2px; /* ↓ smaller */
        }

        .small { font-size:11px; color:#6b7b88; }
        .text-right { text-align:right; }
        .text-left { text-align:left; }

        table { 
            border-collapse: collapse; 
            width:100%; 
        }

        th, td {
            padding: 6px;  /* ↓ Main fix: reduced from 10px to 6px */
        }

        /* Items */
        .items thead th {
            background:#0f4f8a;
            color:#fff;
            padding: 7px; /* ↓ smaller */
        }
        .items td { border:1px solid #d0dbe6; }

        .items tbody tr:nth-child(even) { background:#fbfdff; }

        /* Totals */
        .totals td { padding:6px; }
        .total-row td { 
            border-top:2px solid #c9d7e8; 
            background:#eaf3fb;
            font-weight:700;
        }

        .footer {
            text-align:center; 
            margin-top:10px; /* ↓ smaller */
            font-size:10px;
            color:#98a6b2;
        }
    </style>




<style>
    .otherstable {
        border-collapse: collapse !important;
        width: 100%;
        border-spacing: 0 !important; /* remove table spacing */
    }

    /* reduce row height */
    .otherstable tr {
        line-height: 1.1 !important;   /* tighter */
    }

    /* reduce cell padding */
    .otherstable td, .otherstable th {
        padding: 3px 4px !important;   /* ↓ extremely small padding */
        margin: 0 !important;
    }

    /* header cell */
    .otherstable th {
        background: #0f4f8a;
        color: #fff;
        font-size: 12px;
        border: 1px solid #0f4f8a;
    }

    /* normal cells */
    .otherstable td {
        border: 1px solid #d0dbe6;
        font-size: 12px;
    }


</style>

</head>
<body>

<div class="wrapper">

    {{-- HEADER --}}
    <table>
        <tr>
            <td class="text-center border-bottom" style="text-align:center;">
                <div class="hotel-name">{{ $booking->hotel->name ?? 'Hotel Name' }}</div>
                <div class="small">
                    {{ $booking->hotel->location ?? '' }} •
                    {{ $booking->hotel->contact_number ?? '' }} •
                    {{ $booking->hotel->email ?? '' }}
                </div>
            </td>
        </tr>
    </table>





    {{-- TOP SECTION LEFT + RIGHT --}}
    <table style="margin-top:10px;">
        <tr>
            {{-- LEFT --}}
            <td width="50%" valign="top">
                <div class="section-title">Booking Details</div>

                <table class="otherstable">
                    <tr>
                        <td class="small" width="120">Check-In</td>
                        <td class="small">{{ \Carbon\Carbon::parse($booking->check_in_date)->format('l, F j, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="small">Check-Out</td>
                        <td class="small">{{ \Carbon\Carbon::parse($booking->check_out_date)->format('l, F j, Y') }}</td>
                    </tr>
               
                    <tr>
                        <td class="small">Room</td>
                        <td class="small">{{ $booking->room->name ?? 'Room' }}</td>
                    </tr>
                        <tr>
                        <td class="small">Guests</td>
                        <td class="small">{{ $booking->number_of_guests }}</td>
                    </tr>
                </table>
            </td>

            {{-- RIGHT --}}
            <td width="50%" valign="middle" style="text-align:center;" >
                <div class="section-title" style="font-size: 40px;font-weight:bold;">BOOKING</div>
            </td>
        </tr>
    </table>





    {{-- TOP SECTION LEFT + RIGHT --}}
    <table style="margin-top:10px;" class="otherstable">
        <tr >
            {{-- LEFT --}}
            <td width="50%" valign="top">
               
                <div class="section-title" style="margin-top:6px;">Booked By</div>

                <div class="small">{{ $booking->user_name }}</div>
                <div class="small">{{ $booking->user_email }}</div>
            </td>

            {{-- RIGHT --}}
            <td width="50%" valign="top"  class="text-right">

                <table width="100%">
                    <tr>
                        <td class="small text-left">Booking #</td>
                        <td class="small text-right">{{ $booking->booking_reference }}</td>
                    </tr>
                    <tr>
                        <td class="small text-left">Booking Date</td>
                        <td class="small text-right">{{ $booking->created_at->format('d-m-Y') }}</td>
                    </tr>
                    <tr>
                        <td class="small text-left">Status</td>
                        <td class="small text-right">{{ ucfirst($booking->status) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>





    {{-- ITEMS --}}
    @php
        $nights = $booking->total_nights;
        $unit = $booking->room->price_per_night;
        $line = $unit * $nights;

        $subtotal = $booking->total_amount ?? $line;
        $taxPercent = 0;
        $taxAmount = round($subtotal * $taxPercent/100, 2);
        $grandTotal = $subtotal + $taxAmount;
    @endphp

    <table class="items" style="margin-top:12px;">
        <thead>
            <tr>
                <th width="15%">Qty</th>
                <th width="50%">Description</th>
                <th width="17%" class="text-right">Unit Price</th>
                <th width="18%" class="text-right">Amount</th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td>{{ $nights }}</td>
                <td>
                    {{ $booking->hotel->name }} <br/>
                    {{ $booking->room->room_type }} ({{ $booking->room->room_number }})
                    <div class="small">
                        ({{ \Carbon\Carbon::parse($booking->check_in_date)->format('M j') }}
                        → 
                        {{ \Carbon\Carbon::parse($booking->check_out_date)->format('M j, Y') }})
                    </div>
                </td>
                <td class="text-right">${{ number_format($unit,2) }}</td>
                <td class="text-right">${{ number_format($line,2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- TOTALS --}}
    <table class="totals" style="margin-top:10px;">
        <tr>
            <td width="70%"></td>
            <td width="30%">
                <table width="100%">
                    <tr>
                        <td class="text-right small">Subtotal</td>
                        <td class="text-right">${{ number_format($subtotal,2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-right small">Tax ({{ $taxPercent }}%)</td>
                        <td class="text-right">${{ number_format($taxAmount,2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td class="text-right small">Total</td>
                        <td class="text-right">${{ number_format($grandTotal,2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- FOOTER --}}
    <div class="footer">
        Generated on {{ \Carbon\Carbon::now()->format('F j, Y, g:i A') }}
    </div>

</div>

</body>
</html>
