<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OtpCode extends Model
{
    protected $fillable = ['channel','destination_hash','destination_encrypted','code_hash','purpose','attempts','max_attempts','expires_at','consumed_at','ip','device_id'];
    protected $casts = ['expires_at'=>'datetime','consumed_at'=>'datetime'];
}
