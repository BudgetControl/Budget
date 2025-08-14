<?php
namespace Budgetcontrol\Budget\Domain\Model;

use Budgetcontrol\Library\Model\Budget as Model;
use BudgetcontrolLibs\Crypt\Traits\Crypt;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Budget extends Model
{
    use SoftDeletes, Crypt;
    
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
        'emails',
        'thresholds'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->key = env('APP_KEY');
    }


    public function emails(): Attribute
    {
        $explode = function($value){
            if(empty($value)){
                return [];
            }
            if(is_null($value)){
                return null;
            }
            $explodeValues = explode(',', $value);
            foreach($explodeValues as $key => $email){
                $explodeValues[$key] = $this->decrypt($email);
            }
            // Filtra solo email valide
            return array_filter($explodeValues, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
        };
        $implode = function($value){
            if(empty($value)){
                return [];
            }
            if(is_null($value)){
                return null;
            }
            foreach($value as $key => $email){
                $value[$key] = $this->encrypt($email);
            }
            return implode(',', $value);
        };
        return Attribute::make(
            get: fn (?string $value) => $explode($value),
            set: fn (?array $value) => $implode($value),
        );
    }

    public function thresholds(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? json_decode($value, true) : [],
            set: fn (?array $value) => json_encode($value),
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
