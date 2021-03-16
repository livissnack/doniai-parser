<?php

namespace App\Models;

class Shop extends Model
{
    const NOT_UP = 0;   //不上架
    const UP = 1;       //上架

    public $shop_id;
    public $name;
    public $image;
    public $price;
    public $description;
    public $nums;
    public $sale_nums;
    public $is_up;
    public $mode;
    public $updator_name;
    public $updated_time;
    public $creator_name;
    public $created_time;
}