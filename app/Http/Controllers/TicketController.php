<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Models\TicketComment;
use App\Models\TicketAttachment;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    public function index()
    {
        // Check if user has permission to view tickets
        if (!auth()->user()->can('view-tickets')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view tickets');
        }

        return view('tickets.index');
    }

    public function kanban()
    {
        // Check if user has permission to view tickets
        if (!auth()->user()->can('view-tickets')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view tickets');
        }

        return view('tickets.kanban');
    }

    public function show($id)
    {
        $ticket = Ticket::findOrFail($id);

        return view('tickets.show', compact('ticket'));
    }

    public function create()
    {
        // Check if user has permission to create tickets
        if (!auth()->user()->can('create-tickets')) {
            return redirect()->route('tickets.index')->with('error', 'You do not have permission to create tickets');
        }

        return view('tickets.create');
    }

    public function store(Request $request)
    {
        // Validate request
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
            'type' => 'required|in:bug,feature,update',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max file size
        ]);

        // Create ticket
        $ticket = new Ticket();
        $ticket->title = $request->title;
        $ticket->description = $request->description;
        $ticket->priority = $request->priority;
        $ticket->type = $request->type;
        $ticket->status = 'pending'; // Default status
        $ticket->reporter_id = auth()->id();
        $ticket->save();

        // Handle attachments if present
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ticket-attachments/' . $ticket->id, 'public');
                $ticket->attachments()->create([
                    'path' => $path,
                    'filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Ticket created successfully');
    }

    public function edit($id)
    {
        $ticket = Ticket::findOrFail($id);

        // Check if user has permission to edit this ticket
        if (!auth()->user()->can('edit-tickets') && $ticket->reporter_id !== auth()->id()) {
            return redirect()->route('tickets.show', $ticket->id)
                ->with('error', 'You do not have permission to edit this ticket');
        }

        return view('tickets.edit', compact('ticket'));
    }

    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        // Check if user has permission to update this ticket
        if (!auth()->user()->can('edit-tickets') && $ticket->reporter_id !== auth()->id()) {
            return redirect()->route('tickets.show', $ticket->id)
                ->with('error', 'You do not have permission to update this ticket');
        }

        // Validate request
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
            'type' => 'required|in:bug,feature,update',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max file size
        ]);

        // Update ticket
        $ticket->title = $request->title;
        $ticket->description = $request->description;
        $ticket->priority = $request->priority;
        $ticket->type = $request->type;
        $ticket->save();

        // Handle attachments if present
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ticket-attachments/' . $ticket->id, 'public');
                $ticket->attachments()->create([
                    'path' => $path,
                    'filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Ticket updated successfully');
    }

    public function updateStatus(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        // Check if user has permission to update status
        if (!auth()->user()->can('manage-tickets')) {
            return redirect()->route('tickets.show', $ticket->id)
                ->with('error', 'You do not have permission to update ticket status');
        }

        // Validate status
        $request->validate([
            'status' => 'required|in:pending,approved,ongoing,finished'
        ]);

        // Update status
        $ticket->status = $request->status;

        // If status is approved and no assignee, assign to current user
        if ($request->status == 'approved' && !$ticket->assignee_id) {
            $ticket->assignee_id = auth()->id();
        }

        $ticket->save();

        // Add comment about status change
        $comment = new TicketComment();
        $comment->pharm_ticket_id = $ticket->id;
        $comment->user_id = auth()->id();
        $comment->comment = 'Changed status to ' . ucfirst($request->status);
        $comment->is_system = true;
        $comment->save();

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Ticket status updated successfully');
    }

    public function assign(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        // Check if user has permission to assign tickets
        if (!auth()->user()->can('manage-tickets')) {
            return redirect()->route('tickets.show', $ticket->id)
                ->with('error', 'You do not have permission to assign tickets');
        }

        // Validate user ID
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        // Update assignee
        $ticket->assignee_id = $request->user_id;
        $ticket->save();

        // Add comment about assignment
        $comment = new TicketComment();
        $comment->pharm_ticket_id = $ticket->id;
        $comment->user_id = auth()->id();
        $comment->comment = 'Assigned ticket to ' . $ticket->assignee->name;
        $comment->is_system = true;
        $comment->save();

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Ticket assigned successfully');
    }

    public function addComment(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        // Validate comment
        $request->validate([
            'comment' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max file size
        ]);

        // Create comment
        $comment = new TicketComment();
        $comment->pharm_ticket_id = $ticket->id;
        $comment->user_id = auth()->id();
        $comment->comment = $request->comment;
        $comment->save();

        // Handle attachments if present
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ticket-comments/' . $comment->id, 'public');
                $comment->attachments()->create([
                    'path' => $path,
                    'filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Comment added successfully');
    }

    public function deleteAttachment($id)
    {
        $attachment = TicketAttachment::findOrFail($id);
        $ticketId = $attachment->pharm_ticket_id;

        // Check if user has permission to delete this attachment
        if (
            !auth()->user()->can('manage-tickets') &&
            $attachment->ticket->reporter_id !== auth()->id()
        ) {
            return redirect()->route('tickets.show', $ticketId)
                ->with('error', 'You do not have permission to delete this attachment');
        }

        // Delete file from storage
        Storage::disk('public')->delete($attachment->path);

        // Delete record
        $attachment->delete();

        return redirect()->route('tickets.show', $ticketId)
            ->with('success', 'Attachment deleted successfully');
    }
}
