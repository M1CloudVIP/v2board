<?php

namespace App\Console\Commands;

use App\Jobs\StatServerJob;
use Illuminate\Console\Command;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class V2BoardStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2board:statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '统计任务';

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
     * @return mixed
     */
    public function handle()
    {
        $this->statOrder();
//        $this->statServer();
    }

    private function statOrder()
    {
        $endAt = strtotime(date('Y-m-d'));
        $startAt = strtotime('-1 day', $endAt);
        $builder = Order::where('updated_at', '>=', $startAt)
            ->where('updated_at', '<', $endAt)
            ->whereIn('status', [3, 4]);
        $orderCount = $builder->count();
        $orderAmount = $builder->sum('total_amount');
        $builder = $builder->where('commission_balance', '!=', NULL)
            ->whereIn('commission_status', [1, 2]);
        $commissionCount = $builder->count();
        $commissionAmount = $builder->sum('commission_balance');
        dd([
            $orderCount,
            $orderAmount,
            $commissionCount,
            $commissionAmount
        ]);
    }

    private function statServer()
    {
        $endAt = strtotime(date('Y-m-d'));
        $startAt = strtotime('-1 day', $endAt);
        $statistics = ServerLog::select([
            'server_id',
            'method as server_type',
            DB::raw("sum(u) as u"),
            DB::raw("sum(d) as d"),
        ])
            ->where('log_at', '>=', $startAt)
            ->where('log_at', '<', $endAt)
            ->groupBy('server_id', 'method')
            ->get()
            ->toArray();
        foreach ($statistics as $statistic) {
            $statistic['record_type'] = 'm';
            $statistic['record_at'] = $startAt;
            StatServerJob::dispatch($statistic);
        }
    }
}
