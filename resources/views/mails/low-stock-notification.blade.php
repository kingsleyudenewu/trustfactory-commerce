@component('mail::message')
# Low Stock Alert

The following product is running low on inventory:

**Product**: {{ $product->name }}  
**Current Stock**: {{ $currentStock }} units  
**Reorder Level**: {{ $threshold }} units  
**Price**: ${{ number_format($product->price, 2) }}

## Action Required

Please reorder this product to avoid stock-outs. Check your inventory dashboard for more details.

@component('mail::button', ['url' => config('app.url')])
Go to Dashboard
@endcomponent

Thanks,  
{{ config('app.name') }}

---
*This is an automated low stock notification. Please do not reply to this email.*
@endcomponent
