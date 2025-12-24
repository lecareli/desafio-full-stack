<?php

namespace App\Http\Controllers;

use App\Http\Requests\Wallet\DepositRequest;
use App\Services\Wallet\DepositService;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;
use Throwable;

class WalletController extends Controller
{
    public function __construct(
        protected DepositService $depositService
    ) {}

    public function index()
    {
        return view('dashboard.wallet.index');
    }

    public function deposit(DepositRequest $request): RedirectResponse
    {
        try
        {
            $this->depositService->deposit(
                $request->user(),
                $request->input('amount'),
                $request->input('deposit')
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
}
