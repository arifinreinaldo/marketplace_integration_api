<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShopeeUser extends Model
{
    protected $guarded = ['id'];
    protected $primaryKey = 'id';
    protected $table = 'shopee_user_token';

//    public function image()
//    {
//        return ($this->url_photo) ? asset('/storage/' . $this->url_photo) : '/svg/icon.svg';
//    }
//
//    public function apiImage()
//    {
//        return ($this->url_photo) ? asset('/storage/' . $this->url_photo) : '/svg/icon.svg';
//    }
//
//    public function spesialis()
//    {
//        return $this->hasOne(MSpesialis::class, 'spesialisID', 'spesialis_id');
//    }
//
//    public function tele()
//    {
//        return $this->hasMany(MPoliklinikOnline::class, 'doctor_id', 'id');
//    }
}
