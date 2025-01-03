<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;

class TransactionController extends Controller
{

    public function indexDetail($id)
    {
        $token =  Cookie::get('token');
        $events = Http::withToken($token)->get(env('API_URL') . '/api/events');
        $idSponsor = $id;

        $sponsor = Http::get(env('API_URL') . '/api/sponsor/' . $id);

        return view('event.detail', [
            'events' => json_decode($events),
            'idSponsor' => $idSponsor,
            'sponsor' => json_decode($sponsor)
        ]);
    }
    public function store(Request $request)
    {
        $data = [
            'id_event' => $request->id_event,
            'id_sponsor' => $request->id_sponsor,
        ];
        $token =  Cookie::get('token');
        $trans = Http::withToken($token)->post(env('API_URL') . '/api/transaction', $data);

        if ($trans->getStatusCode() == 201) {
            return redirect('/event/status')->with('success', 'Proposal berhasil dikirim');
        } else {
            return redirect('/event/sponsors')->with('error', 'Proposal gagal dikirim');
        }
    }

    public function update(Request $request)
    {
        try {
            // Validasi panjang pesan
            if (strlen($request->comment) < 3) {
                return redirect('/sponsor/event')
                    ->with('error', 'Teks pesan kurang dari 3 karakter');
            }

            $data = [
                'id' => $request->id,
                'id_status' => $request->id_status,
                'comment' => $request->comment,
                'total_fund' => $request->total_fund,
                'id_level' => $request->id_level
            ];


            $response = Http::patch(env('API_URL') . '/api/transaction', $data);


            if ($response->successful()) {
                if ($request->id_status == 2) { // Terima proposal
                    return redirect('/sponsor/payment')
                        ->with('success', 'Respon telah terkirim');
                } else { // Tolak proposal
                    return redirect('/sponsor/event')
                        ->with('success', 'Respon telah terkirim');
                }
            }

            return redirect('/sponsor/event')
                ->with('error', $response->json()['message'] ?? 'Terjadi kesalahan');
        } catch (\Exception $e) {
            return redirect('/sponsor/event')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
