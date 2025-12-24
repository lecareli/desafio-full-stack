<?php

namespace App\Http\Controllers;

use App\Exceptions\Wallet\AlreadyReversedException;
use App\Exceptions\Wallet\InsufficientBalanceException;
use App\Exceptions\Wallet\NotAllowedToReverseException;
use App\Exceptions\Wallet\RecipientNotFoundException;
use App\Exceptions\Wallet\TransactionNotFoundException;
use App\Http\Requests\Wallet\DepositRequest;
use App\Http\Requests\Wallet\TransferRequest;
use App\Models\Transaction;
use App\Services\Wallet\DepositService;
use App\Services\Wallet\ReverseTransactionService;
use App\Services\Wallet\TransferService;
use App\Services\Wallet\WalletService;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;
use Throwable;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService,
        protected DepositService $depositService,
        protected TransferService $transferService,
        protected ReverseTransactionService $reverseService
    ) {}

    public function index()
    {
        $user = request()->user();

        $wallet = $this->walletService->getOrCreateWallet($user);

        $transactions = Transaction::query()
            ->where(function ($q) use ($wallet){
                $q->where('from_wallet_id', $wallet->id)
                    ->orWhere('to_wallet_id', $wallet->id);
            })
            ->when(request('q'), function ($q, $term) {
                $q->where(function ($w) use ($term) {
                    $w->where('description', 'ilike', "%{$term}%")
                        ->orWhere('type', 'ilike', "%{$term}%");
                });
            })
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('dashboard.wallet.index', [
            'walletBalanceCents' => (int) $wallet->balance_cents,
            'walletCurrency' => $wallet->currency ?? 'BRL',
            'walletUpdatedAt' => optional($wallet->updated_at)?->format('d/m/Y H:i') ?? '—',
            'transactions' => $transactions,
        ]);
    }

    public function deposit(DepositRequest $request): RedirectResponse
    {
        try
        {
            $this->depositService->deposit(
                $request->user(),
                $request->input('amount'),
                $request->input('description')
            );

            return back()->with('success', 'Depósito realizado com sucesso.');
        }
        catch(InvalidArgumentException $e)
        {
            return back()
                ->withErrors(['amount' => $e->getMessage()])
                ->withInput();
        }
        catch(Throwable $e)
        {
            return back()
                ->with('error', 'Não foi possível realizar o depósito. Tente novamente.')
                ->withInput();
        }
    }

    public function transfer(TransferRequest $request): RedirectResponse
    {
        try
        {
            $this->transferService->transfer(
                $request->user(),
                $request->input('to_email'),
                $request->input('amount'),
                $request->input('description')
            );

            return back()->with('success', 'Transferência realizada com sucesso.');
        }
        catch(RecipientNotFoundException $e)
        {
            return back()
                ->withErrors(['to_email' => $e->getMessage()])
                ->withInput();
        }
        catch(InsufficientBalanceException $e)
        {
            return back()
                ->withErrors(['amount' => $e->getMessage()])
                ->withInput();
        }
        catch(InvalidArgumentException $e)
        {
            return back()
                ->withErrors(['amount' => $e->getMessage()])
                ->withInput();
        }
        catch(Throwable $e)
        {
            return back()
                ->with('error', 'Não foi possível realizar a transferência. Tente novamente.')
                ->withInput();
        }
    }

    public function reverse(string $transactionId): RedirectResponse
    {
        try
        {
            $this->reverseService->reverse(request()->user(), $transactionId);
            return back()->with('success', 'reversão realizada com sucesso.');
        }
        catch(AlreadyReversedException $e)
        {
            return back()->with('success', 'Esta transação já estava revertida.');
        }
        catch(TransactionNotFoundException|NotAllowedToReverseException $e)
        {
            return back()->with('error', $e->getMessage());
        }
        catch(InsufficientBalanceException $e)
        {
            return back()->with('error', $e->getMessage());
        }
        catch(Throwable $e)
        {
            return back()->with('error', 'Não foi possível reverter a transação. Tente novamente.');
        }
    }
}
