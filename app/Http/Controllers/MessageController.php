<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Display a listing of the messages.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login to view messages.');
        }

        // In a real app, you would fetch messages from the database
        // For now, we'll return a view with sample data
        return view('messages.index');
    }

    /**
     * Display the inbox messages.
     *
     * @return \Illuminate\Http\Response
     */
    public function inbox()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login to view messages.');
        }

        // In a real app, you would fetch received messages from the database
        return view('messages.inbox');
    }

    /**
     * Display the sent messages.
     *
     * @return \Illuminate\Http\Response
     */
    public function sent()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login to view messages.');
        }

        // In a real app, you would fetch sent messages from the database
        return view('messages.sent');
    }

    /**
     * Show the form for creating a new message.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('messages.create');
    }

    /**
     * Store a newly created message in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'recipient' => 'required',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // In a real app, you would store the message in the database
        
        return redirect()->route('messages.sent')
            ->with('success', 'Message sent successfully.');
    }

    /**
     * Display the specified message.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // In a real app, you would fetch the message from the database
        
        return view('messages.show');
    }

    /**
     * Mark a message as read.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function markAsRead($id)
    {
        // In a real app, you would mark the message as read in the database
        
        return redirect()->back()
            ->with('success', 'Message marked as read.');
    }

    /**
     * Delete the specified message.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // In a real app, you would delete the message from the database
        
        return redirect()->route('messages.inbox')
            ->with('success', 'Message deleted successfully.');
    }
}
