@component('mail::message')
# Daily Sales Report

**Report Date**: {{ $report->report_date->format('F j, Y') }}

## Summary Metrics

| Metric | Value |
|--------|-------|
| Total Items Sold | {{ $report->total_items_sold }} |
| Unique Products Sold | {{ $report->unique_products_sold }} |
| Total Revenue | ${{ number_format($report->total_revenue, 2) }} |

## Top 10 Best Selling Products

@if($report->top_products && $report->top_products->count() > 0)
| Product | Quantity | Revenue |
|---------|----------|---------|
@foreach($report->top_products as $product)
| {{ $product['product_name'] }} | {{ $product['quantity'] }} | ${{ number_format($product['revenue'], 2) }} |
@endforeach
@else
No sales recorded for this period.
@endif

## Report Details

- **Report Generated**: {{ $report->created_at->format('F j, Y g:i A') }}
- **Report Sent**: {{ $report->sent_at ? $report->sent_at->format('F j, Y g:i A') : 'Pending' }}

@component('mail::button', ['url' => config('app.url') . '/dashboard'])
View Dashboard
@endcomponent

Thanks,  
{{ config('app.name') }} Admin Team

---
*This is an automated daily sales report. Please do not reply to this email.*
@endcomponent
