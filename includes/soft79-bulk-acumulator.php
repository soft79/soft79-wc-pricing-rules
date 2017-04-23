<?php

/**
 *  This class is used to acumulate the values of rules that apply to a product and return the new average/min/max price
 *  
 */
class SOFT79_Price_Acumulator {
    protected $total_price = 0;
    protected $total_qty = 0;
    protected $min_price = null;
    protected $max_price = null;
    
    protected $quantities = array();
    protected $prices = array();
    
    public function total_price() {
        return $this->total_price;
    }
    
    public function total_qty() {
        return $this->total_qty;
    }    
    
    public function avg_price() {
        if ( $this->total_qty == 0 ) {
            return null;
        }
        return $this->total_price / $this->total_qty;
    }
    
    public function has_a_value() {
        return $this->total_qty > 0;
    }
    
    public function min_price() {
        return min( $this->prices );
    }
    
    public function max_price() {
        return max( $this->prices );
    }    
    
    public function add( $qty, $unit_price ) {
        
        $this->quantities[] = $qty;
        $this->prices[] = $unit_price;
        
        $this->total_price += $qty * $unit_price;
        $this->total_qty += $qty;
        
        if ( $unit_price < $this->min_price || $this->min_price === null ) {
            $this->min_price = $unit_price;
        }
        
        if ( $unit_price > $this->max_price || $this->max_price === null ) {
            $this->max_price = $unit_price;
        }        
    }
}