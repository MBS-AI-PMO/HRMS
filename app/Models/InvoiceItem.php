<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
	protected $fillable = [
		'item_name', 'invoice_id', 'project_id', 'item_tax_type', 'item_tax_rate', 'sub_total', 'discount_type', 'discount_figure', 'total_tax',
		'total_discount', 'grand_total', 'item_unit_price', 'item_sub_total','item_qty'
	];

	public function project(){
		return $this->hasOne('App\Models\Project','id','project_id');
	}
	public function invoice(){
		return $this->hasOne('App\Models\Invoice','id','invoice_id');
	}

    public function getItemUnitPriceAttribute($value)
    {
        return number_format($value, 2, '.', '');
    }

    public function getItemTaxRateAttribute($value)
    {
        return number_format($value, 2, '.', '');
    }
    public function getSubTotalAttribute($value)
    {
        return number_format($value, 2, '.', '');
    }
    public function getTotalTaxAttribute($value)
    {
        return number_format($value, 2, '.', '');
    }

    public function getItemSubTotalAttribute($value)
    {
        return number_format($value, 2, '.', '');
    }


}
