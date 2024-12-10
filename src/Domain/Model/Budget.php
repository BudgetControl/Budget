<?php
namespace Budgetcontrol\Budget\Domain\Model;

use Budgetcontrol\Budget\Domain\Model\Labels;
use Budgetcontrol\Library\Model\Budget as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Budget extends Model
{
    use SoftDeletes;
    
    const ONE_SHOT = 'one_shot';
    const RECURSIVELY = 'recursively';
    const DAILY = 'daily';
    const WEEKLY = 'weekly';
    const MONTHLY = 'monthly';
    const YEARLY = 'yearly';

    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    
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
    ];


    public function emails(): Attribute
    {
        $explode = function($value){
            if(empty($value)){
                return [];
            }
            return explode(',', $value);
        };

        $implode = function($value){
            if(empty($value)){
                return [];
            }
            return implode(',', $value);
        };

        return Attribute::make(
            get: fn (string $value) => $explode($value),
            set: fn (array $value) => $implode($value),
        );
    }

    public function configuration(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => json_decode($value, true),
            set: fn (array $value) => json_encode($value),
        );
    }
    

}
