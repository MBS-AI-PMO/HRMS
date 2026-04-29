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
<div class="header">
    <div class="company"> {{ $announcement->company->company_name ?? 'Company Name' }} </div>
    <div class="date"> Generated on: {{ date('d M Y') }} </div>
</div>
<h2>Announcement</h2>

<p><strong>Title:</strong> {{ $announcement->title }}</p>
<p><strong>Summary:</strong> {{ $announcement->summary }}</p>

<p><strong>Description:</strong></p>
<p>{!! $announcement->description !!}</p>

<p><strong>Company:</strong> {{ $announcement->company->company_name ?? '' }}</p>
<p><strong>Department:</strong> {{ $announcement->department->department_name ?? 'All' }}</p>

<p><strong>Start Date:</strong> {{ $announcement->start_date }}</p>
<p><strong>End Date:</strong> {{ $announcement->end_date }}</p>
<div class="footer"> © {{ date('Y') }} {{ $announcement->company->company_name ?? '' }} — All rights reserved </div>
</body>
</html>
