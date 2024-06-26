<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Cache\Cache;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Mailer\Mailer;
use Cake\ORM\TableRegistry;

/**
 * SendMail command.
 */
class SendMailCommand extends Command
{
    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
     * @param ConsoleOptionParser $parser The parser to be defined
     * @return ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param Arguments $args The command arguments.
     * @param ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $extended = Cache::read('settings.extended', 'extended') ?? false;

        if ((!$extended && Time::now()->minute < 10) || ($extended && Time::now()->minute > 10)) {
            $ordersTable = $this->loadModel('Orders');
            $paypalMes = $this->loadModel('Paypalmes');
            $payhistory = $this->loadModel('Payhistory');
            $orders = $ordersTable->find()->where([
                'for' => (new Date())->toIso8601String(),
            ])->group(['Hirsch.name', 'note'])->select(['Hirsch.name', 'for', 'note', 'cnt' => 'count(Hirsch.name)'])->contain(['Hirsch']);

            $orderer = $ordersTable->find()->where(['for' => (new Date())->toIso8601String()])->select('orderedby');

            $first = true;
            $out = '';
            foreach ($orders as $order) {
                if (!$first) {
                    $out .= PHP_EOL . PHP_EOL;
                }
                $out .= $order->cnt . 'x ' . $order->hirsch->name;
                if (!empty($order->note)) {
                    $out .= PHP_EOL . 'Sonderwunsch: ' . $order->note;
                }
                $first = false;
            }

            $active = $payhistory->find()->select([
                'cnt' => 'COUNT(*)',
                'paypalme_id',
            ])->where([
                'created >' => (new Time())->startOfDay(),
            ])->group(['paypalme_id'])->max('cnt');
    
            if ($active) {
                $currentReceiver = $paypalMes->get($active->paypalme_id);
            } else {
                $currentReceiver = null;
            }
            if (!empty($out) && !empty($currentReceiver)) {
                $out .= PHP_EOL . PHP_EOL . PHP_EOL . 'Besteller:' . PHP_EOL . PHP_EOL;
                foreach ($orderer as $item) {
                    $out .= $item->orderedby . PHP_EOL;
                }

                $mailer = new Mailer('default');
                $mailer->viewBuilder()->setTemplate('orders');
                $mailer->setDomain('hochwarth-e.com');
                $mailer->setFrom(['essen@hochwarth-e.com' => 'Hirsch Bestellseite'])
                    ->setTo([$currentReceiver->email => $currentReceiver->name])
                    ->setSubject('🦌 Hirsch Bestellungen vom ' . new Date())
                    ->setEmailFormat('both')
                    ->deliver($out);
            }
        }
    }
}
