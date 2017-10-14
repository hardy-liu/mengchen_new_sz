<?php

namespace App\Console\Commands;

use App\Models\StatementDaily;
use App\Services\Game\PlayerService;
use App\Services\Game\StatementDailyService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateDailyStatement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:generate-daily-statement 
                            {--init : 初始化每日数据报表}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        if ($this->option('init')) {
            $this->initializeDailyStatement();
            return $this->info('初始化每日数据报表数据完成');
        }

        //每天凌晨运行，生成昨天的报表数据
        $yesterday = Carbon::yesterday()->toDateString();
        $this->generateDailyStatement($yesterday);
        return $this->info($yesterday . ': 数据报表生成完成');
    }

    protected function initializeDailyStatement()
    {
        $date = collect(PlayerService::getAllPlayers())
            ->sortBy('create_time')
            ->groupBy(function ($item, $key) {
                return Carbon::parse($item['create_time'])->toDateString();
            })
            ->keys()
            ->first();

        //从第一个用户创建的那一天开始，生成每天的数据报表
        while (true) {
            //生成今天之前的数据，今天的数据不生成
            if (Carbon::parse($date)->isToday()) {
                break;
            }
            $this->generateDailyStatement($date);
            $date = Carbon::parse($date)->addDay(1)->toDateString();
        }
    }

    /**
     * 生成每日报表数据，入库
     *
     * @param string $date '格式2017-09-11'
     */
    protected function generateDailyStatement($date)
    {
        $data = [];
        $statementDailyService = new StatementDailyService();
        $data['date'] = $date;
        $data['peak_online_players'] = $statementDailyService->getPeakOnlinePlayersAmount();
        $data['active_players'] = $statementDailyService->getActivePlayersAmount($date);
        $data['incremental_players'] = $statementDailyService->getIncrementalPlayersAmount($date);
        $data['one_day_remained'] = $statementDailyService->getRemainedData($date, 1);
        $data['one_week_remained'] = $statementDailyService->getRemainedData($date, 7);
        $data['two_weeks_remained'] = $statementDailyService->getRemainedData($date, 14);
        $data['one_month_remained'] = $statementDailyService->getRemainedData($date, 30);
        $data['card_consumed_data'] = $statementDailyService->getCardConsumedData($date);
        $data['card_bought_data'] = $statementDailyService->getCardBoughtData($date);
        $data['card_consumed_sum'] = $statementDailyService->getCardConsumedSum($date);
        $data['card_bought_sum'] = $statementDailyService->getCardBoughtSum($date);
        $data['players_data'] = json_encode(PlayerService::getAllPlayers());

        return StatementDaily::create($data);
    }
}