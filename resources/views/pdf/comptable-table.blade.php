<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }
        h1 { font-size: 14px; margin: 0 0 4px; }
        .meta { font-size: 8px; color: #555; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 5px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        td.num { text-align: right; }
        .footer { margin-top: 10px; font-size: 8px; color: #888; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        @foreach ($meta as $k => $v)
            <span><strong>{{ $k }}:</strong> {{ $v }}</span>
            @if (!$loop->last) &nbsp;|&nbsp; @endif
        @endforeach
        <br>Genere le {{ $generated_at }}
    </div>
    <table>
        <thead>
        <tr>
            @foreach ($headers as $h)
                <th>{{ $h }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach ($rows as $row)
            <tr>
                @foreach ($row as $i => $cell)
                    <td>{{ $cell }}</td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="footer">SALAMA Accounting</div>
</body>
</html>
