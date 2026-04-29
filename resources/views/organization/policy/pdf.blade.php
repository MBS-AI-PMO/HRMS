<!DOCTYPE html>

<html>
<head>
    <title>Policy PDF</title>


<style>
    body {
        font-family: DejaVu Sans, sans-serif;
        margin: 0;
    }

    /* HEADER */
    .header {
        border-bottom: 1px solid #ccc;
        padding: 10px 20px;
        margin-bottom: 20px;
    }
    .company {
        font-size: 18px;
        font-weight: bold;
        color: #2d73db;
    }
    .date {
        font-size: 12px;
        color: #555;
    }
    .content {
        padding: 0 20px;
    }
    h2 {
        text-align: center;
    }
    p {
        margin-bottom: 10px;
    }

    /* FOOTER */
    .footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 11px;
        color: #777;
        border-top: 1px solid #ccc;
        padding: 5px 0;
    }
</style>


</head>

<body>

<!-- HEADER -->
<div class="header">
    <div class="company">
        {{ $policy->company->company_name ?? 'Company Name' }}
    </div>
    <div class="date">
        Generated on: {{ date('d M Y') }}
    </div>
</div>

<!-- CONTENT -->
<div class="content">
    <h2>Company Policy</h2>

    <p><strong>Title:</strong> {{ $policy->title }}</p>
    

    <p><strong>Description:</strong></p>
    <p>{{ $policy->description }}</p>

    
</div>

<!-- FOOTER -->
<div class="footer">
    © {{ date('Y') }} {{ $policy->company->company_name ?? '' }} — All rights reserved
</div>


</body>
</html>
