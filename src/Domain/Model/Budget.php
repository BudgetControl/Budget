<?php
namespace Budgetcontrol\Budget\Domain\Model;

use Budgetcontrol\Budget\Domain\Model\Labels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use SoftDeletes;
    
    const ONE_SHOT = 'one_shot';
    const DAILY = 'daily';
    const WEEKLY = 'weekly';
    const MONTHLY = 'monthly';
    const YEARLY = 'yearly';
    
    protected $fillable = [
        'uuid',
        'budget',
        'configuration',
        'notification',
        'workspace_id',
        'emails'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'id'
    ];

    public function setConfigurationAttribute($value)
    {
        $this->attributes['configuration'] = json_decode($value);
    }

    public function setConfigurationNotification($value)
    {
        $this->attributes['notification'] = (bool) $value;
    }

    public function setConfigurationEmails($value)
    {
        $this->attributes['emails'] = explode(',',$value);
    }
    

}