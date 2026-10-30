<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SolarAssistantService;

class ChatbotController extends Controller
{
    public function assistant(Request $request, SolarAssistantService $assistant)
    {
        $request->validate([
            'message'=>['nullable','string','max:1000'],'action'=>['nullable','string','max:40'],
            'module'=>['nullable','string','max:40'],'status'=>['nullable','string','max:40'],
            'period'=>['nullable','string','max:30'],'search'=>['nullable','string','max:255'],
            'page'=>['nullable','integer','min:1','max:10000'],'equipment'=>['nullable','boolean'],
            'create_ticket'=>['nullable','boolean'],
        ]);
        if ($request->boolean('create_ticket')) return response()->json([
            'reply'=>$request->user()->hasMatrixPermission('create_tickets') ? 'Open Add Ticket to choose the customer and complete your support request.' : 'You do not have permission to create tickets.',
            'actions'=>$request->user()->hasMatrixPermission('create_tickets') ? [['label'=>'Add Ticket','url'=>route('tickets.create')]] : [],
        ]);
        $answer=$assistant->answer($request);
        $answer['as_of'] ??= now()->timezone('Asia/Kolkata')->format('d M Y, h:i A').' IST';
        return response()->json($answer);
    }
}
