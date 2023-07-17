<?php

namespace Books\Wallet\Behaviors;

use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Models\Wallet as WalletModel;
use Bavix\Wallet\Traits\HasWallet;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use October\Rain\Database\ModelBehavior;

class WalletBehavior extends ModelBehavior
{
    /**
     * UserModelBehavior constructor.
     * @param $model
     */
    public function __construct($model)
    {
        parent::__construct($model);
    }

    public function proxyWallet(): Wallet
    {
        $proxyClass = new class extends Model implements Wallet
        {
            use HasWallet;

            public $exists = true;

            /**
             * @var Model|Authenticatable
             */
            private Model|Authenticatable $model;

            /**
             * @param Model|Authenticatable $model
             * @return $this
             */
            public function setModel(Model|Authenticatable $model)
            {
                $this->model = $model;

                return $this;
            }

            /**
             * Get default Wallet
             * this method is used for Eager Loading
             *
             * @return MorphOne
             */
            public function wallet(): MorphOne
            {
                return ($this->model instanceof WalletModel ? $this->model->holder : $this->model)
                    ->morphOne(config('wallet.wallet.model'), 'holder')
                    ->where('slug', config('wallet.wallet.default.slug'))
                    ->withDefault([
                        'name' => config('wallet.wallet.default.name'),
                        'slug' => config('wallet.wallet.default.slug'),
                        'balance' => 0,
                    ]);
            }

            /**
             * all user actions on wallets will be in this method
             *
             * @return MorphMany
             */
            public function transactions(): MorphMany
            {
                return ($this->model instanceof WalletModel ? $this->model->holder : $this->model)
                    ->morphMany(config('wallet.transaction.model'), 'payable');
            }

            /**
             * @return mixed
             */
            public function getWalletAttribute()
            {
                return $this->wallet()->getResults();
            }
        };

        return (new $proxyClass())
            ->setModel($this->model)
            ->setRawAttributes($this->model->toArray());
    }

    /**
     * @return Wallet
     */
    public function getWalletAttribute(): Wallet
    {
        return $this->proxyWallet();
    }
}
