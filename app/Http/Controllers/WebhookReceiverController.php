<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use Inertia\Inertia;
use ReflectionMethod;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\WebhookReceiver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Laravel\Jetstream\RedirectsActions;
use Illuminate\Support\Facades\Validator;
use NotificationChannels\Telegram\Telegram;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\WebhookReceiver as ResourcesWebhookReceiver;

class WebhookReceiverController extends Controller
{
    use RedirectsActions;

    public function show(Request $request)
    {
        $webhookReceivers = WebhookReceiver::with(['bot', 'user'])->whereTeamId($request->user()->currentTeam->id)
            ->orderBy('created_at', 'desc')->get();

        return Inertia::render('Webhook/Show', [
            'webhookReceivers' => ResourcesWebhookReceiver::collection($webhookReceivers),
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Webhook/Create');
    }

    public function relink(Request $request)
    {
        Validator::make($request->all(), [
            'id' => ['required'],
        ])->validateWithBag('botLink');

        $webhookReceiver = WebhookReceiver::with('bot')->find($request->id);

        Cache::put(
            $webhookReceiver->token,
            auth()->user()->id . ' ' . auth()->user()->currentTeam->id . ' ' . $webhookReceiver->bot->id,
            3600
        );

        return back()->with([
            "url" => 'https://t.me/' . $webhookReceiver->bot->username . '?startgroup=' . $webhookReceiver->token,
            'token' => $webhookReceiver->token,
        ]);
    }

    public function edit(Request $request)
    {
        if (!$webhookReceiver = WebhookReceiver::find($request->id)) {
            return redirect()->intended(config('fortify.home'));
        }

        return Inertia::render('Webhook/Edit', [
            'webhookReceiver' => new ResourcesWebhookReceiver($webhookReceiver),
        ]);
    }

    public function update(Request $request, $webhookReceiverId)
    {
        $webhookReceiver = WebhookReceiver::findOrFail($webhookReceiverId);

        Validator::make($request->all(), [
            'jmte' => ['string'],
        ])->validateWithBag('updateWebhookReceiver');

        try {
            $webhookReceiver->forceFill([
                'jmte' => $request->jmte,
            ])->save();
        } catch (\Throwable $th) {
            throw $th;
            throw ValidationException::withMessages([
                'jmte' => '儲存失敗。',
            ])->errorBag('updateWebhookReceiver');
        }

        return back(303);
    }

    public function destroy(Request $request, $webhookReceiverId)
    {
        $webhookReceiver = WebhookReceiver::findOrFail($webhookReceiverId);

        $webhookReceiver->delete();

        return redirect()->intended(route('webhooks'));
    }
}
