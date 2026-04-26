<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Get all users for the chat list.
     */
    public function getUsers()
    {
        $users = User::where('id', '!=', Auth::id())
            ->select('id', 'full_name')
            ->addSelect([
                'unread_count' => Message::selectRaw('count(*)')
                    ->whereColumn('sender_id', 'mst_user.id')
                    ->where('receiver_id', Auth::id())
                    ->where('is_read', false)
            ])
            ->get();
        return response()->json($users);
    }

    /**
     * Get chat history between the authenticated user and another user.
     */
    public function getMessages($receiverId)
    {
        $messages = Message::where(function ($query) use ($receiverId) {
            $query->where('sender_id', Auth::id())
                ->where('receiver_id', $receiverId);
        })->orWhere(function ($query) use ($receiverId) {
            $query->where('sender_id', $receiverId)
                ->where('receiver_id', Auth::id());
        })
        ->with(['sender:id,full_name', 'receiver:id,full_name'])
        ->orderBy('created_at', 'asc')
        ->get();

        return response()->json($messages);
    }

    /**
     * Send a new message.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:mst_user,id',
            'message' => 'required|string',
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'is_read' => false,
        ]);

        broadcast(new MessageSent($message->load('sender')))->toOthers();

        return response()->json([
            'status' => 'Message Sent!',
            'message' => $message
        ]);
    }

    /**
     * Mark messages from a specific user as read.
     */
    public function markAsRead($senderId)
    {
        Message::where('sender_id', $senderId)
            ->where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['status' => 'success']);
    }
}
