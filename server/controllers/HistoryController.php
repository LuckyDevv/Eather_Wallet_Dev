<?php

namespace controllers;

use db_managers\TransactionsDB;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

date_default_timezone_set('Europe/Moscow');

class HistoryController extends Controller
{
    private Environment $twig;
    public bool $typeAll;
    public int $id;
    public string $transaction_id;
    public function __construct(bool $typeAll, Environment $twig, int $id=0, string $transaction_id="") {
        if ($transaction_id == "") {
            $this->typeAll = true;
        }else{
            $this->typeAll = $typeAll;
        }
        $this->id = $id;
        $this->transaction_id = $transaction_id;
        $this->twig = $twig;
    }
    public function handle(): array|string
    {
        if ($this->id !== 0) {
            if ($this->typeAll) {
                return $this->fetchAll();
            }else{
                return $this->fetchOne();
            }
        }else return "";
    }

    private function fetchAll(): string{
        try {
            $all_transactions = (new TransactionsDB())->getTransactionsByUserId($this->id);
            $expenses = 0; $receipts = 0; $transactions = "";
            $history_stop_text = (count($all_transactions) > 0) ? "На этом история ваших операций закончилась..." : "У вас ещё не было ни одной транзакции. Время это исправить! :)";
            foreach ($all_transactions as $transaction) {
                $transaction["sort"] ? $expenses+=$transaction["amount"] : $receipts+=$transaction["amount"];
                $amount = $transaction["sort"] ? '- ' . number_format($transaction["amount"], 2, ',', ' ') . ' ' . "₽" : '+ ' . number_format($transaction["amount"], 2, ',', ' ') . ' ' . "₽";
                $receipt = $transaction["sort"] ? '' : 'receipt';
                $wallet_id = $transaction["sort"] ? $transaction["from_id"] : $transaction["to_id"];
                $from_id = $transaction["sort"] ? $transaction["to_id"] : $transaction["from_id"];
                $from_name = $transaction["sort"] ? $transaction["to_name"] : $transaction["from_name"];
                $is_receipt = $transaction["sort"] ? "false" : "true";
                $transactions .= $this->twig->render("transaction.twig", [
                    'path' => 'src/images/user.jpg',
                    'name' => $transaction["trans_name"],
                    'type' => $transaction["sort"],
                    'date' => date('d.m.Y H:i', $transaction["timestamp"]),
                    'amount' => $amount,
                    'receipt' => $receipt,
                    'real_paid' => $transaction["amount"],
                    // METADATA
                    'wallet' => $wallet_id,
                    'description' => $transaction["comment"],
                    'from' => $from_name,
                    'from_id' => $from_id,
                    't_id' => $transaction["trans_id"],
                    't_type' => $transaction["sort_type"],
                    'is_receipt' => $is_receipt,
                ]);
            }
            return $this->twig->render("history.twig", [
                'month' => $this->monthToString(),
                'real_expenses' => floor((float) $expenses * 100) / 100,
                'real_receipts' => floor((float) $receipts * 100) / 100,
                'transactions' => $transactions,
                'stop_text' => $history_stop_text,
            ]);
        }catch(LoaderError|RuntimeError|SyntaxError $e) {
            $this->writeExceptionLog("HistoryController", $e);
            return "";
        }
    }

    private function fetchOne(): array
    {
        $transaction = (new TransactionsDB())->getTransactionById($this->transaction_id, $this->id);
        $expenses = 0;
        $receipts = 0;
        if (count($transaction) > 0) {
            $transaction = $transaction[0];
            $transaction["sort"] ? $expenses+=$transaction["amount"] : $receipts+=$transaction["amount"];
            $amount = $transaction["sort"] ? '- ' . number_format($transaction["amount"], 2, ',', ' ') . ' ' . "₽" : '+ ' . number_format($transaction["amount"], 2, ',', ' ') . ' ' . "₽";
            $receipt = $transaction["sort"] ? '' : 'receipt';
            $wallet_id = $transaction["sort"] ? $transaction["from_id"] : $transaction["to_id"];
            $from_id = $transaction["sort"] ? $transaction["to_id"] : $transaction["from_id"];
            $from_name = $transaction["sort"] ? $transaction["to_name"] : $transaction["from_name"];
            $is_receipt = $transaction["sort"] ? "false" : "true";
            $transactions = $this->twig->render("transaction.twig", [
                'path' => 'src/images/user.jpg',
                'name' => $transaction["trans_name"],
                'type' => $transaction["sort"],
                'date' => date('d.m.Y H:i', $transaction["timestamp"]),
                'amount' => $amount,
                'receipt' => $receipt,
                'real_paid' => $transaction["amount"],
                // METADATA
                'wallet' => $wallet_id,
                'description' => $transaction["comment"],
                'from' => $from_name,
                'from_id' => $from_id,
                't_id' => $transaction["trans_id"],
                't_type' => $transaction["type"],
                'is_receipt' => $is_receipt,
            ]);
        }
        return ["transaction" => $transactions ?? "", "expenses" => $expenses, "receipts" => $receipts];
    }

    private function monthToString(): string
    {
        $months = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
        $month = date('n') - 1;
        return $months[$month];
    }

    public function parseHistoryError(string $text="Не удалось загрузить историю операций... Попробуйте позже."): string
    {
        return $this->twig->render("history.twig", [
            "month" => $this->monthToString(),
            "transactions" => "",
            "real_expenses" => 0.0,
            "real_receipts" => 0.0,
            "stop_text" => $text
        ]);
    }
}