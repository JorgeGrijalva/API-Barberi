<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Bonus;
use App\Models\Discount;
use Illuminate\Console\Command;
use Log;
use Throwable;

class RemoveExpiredAt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:expired:models';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'remove expired closed dates';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $discounts = Discount::with(['stocks'])
            ->whereDate('end', '<', now()->format('Y-m-d'))
            ->where('active', true)
            ->get();

        foreach ($discounts as $discount) {
            try {

                /** @var Discount $discount */
                $discount->stocks()->update([
                    'discount_expired_at' => null,
                ]);

                $discount->update([
                    'active' => 0,
                ]);

            } catch (Throwable $e) {
                Log::error($e->getMessage());
            }
        }

        $bonuses = Bonus::with(['bonusStock'])
            ->whereDate('expired_at', '<', now()->format('Y-m-d H:i:s'))
            ->where('status', true)
            ->get();

        foreach ($bonuses as $bonus) {
            try {

                /** @var Bonus $bonus */
                $bonus->bonusStock()->update([
                    'bonus_expired_at' => null,
                ]);

                $bonus->update([
                    'status' => 0,
                ]);

            } catch (Throwable $e) {
                Log::error($e->getMessage());
            }
        }

        return 0;
    }
}
