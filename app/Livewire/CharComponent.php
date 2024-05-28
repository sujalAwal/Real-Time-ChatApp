<?php

namespace App\Livewire;

use App\Events\MessageSentEvent;
use App\Models\Message;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class CharComponent extends Component
{

    public $user;
    public $sender_id;
    public $receiver_id;
    public $message="";
    public $messages=[];
    public function render()
    {
        return view('livewire.char-component');
    }

    public function mount($user_id){

        $this->sender_id= Auth()->user()->id;
        $this->receiver_id = $user_id;
        $this->user = User::whereId($user_id)->first();

        $displayMsg = Message::where(function($query){
            $query->where('sender_id',$this->sender_id)
            ->where('receiver_id',$this->receiver_id);
        })->orWhere(function($query){
            $query->where('sender_id',$this->receiver_id)
            ->where('receiver_id',$this->sender_id);
        })->with('sender:id,name','receiver:id,name')->get();

        foreach($displayMsg as $msg){
            $this->displayMsg($msg);
        }
        // dd($this->messages);


    }

    public function sendMessage(){

        try{

            $msg = new Message();
            $msg->sender_id = $this->sender_id;
            $msg->receiver_id = $this->receiver_id;
            $msg->message = $this->message;
            $msg->save();

            $this->displayMsg($msg);    
            broadcast(new MessageSentEvent($msg))->toOthers();
            
            $this->message="";
        }
        catch(Exception $e){
            dd($e);
        }

        // dd($this->message);

    }
    #[On('echo-private:chat-channel.{sender_id},MessageSentEvent')]
    public function listner($event){
     $msg = Message::whereId($event['message']['id'])
                   ->with('sender:id,name','receiver:id,name')
                   ->first();

                   $this->displayMsg($msg);
        
    }

    public function displayMsg($msg){

        // dd($msg);
        $this->messages[]=[
            'id'=>$msg->id,
          'message'=>$msg->message,
          'sender_name'=> $msg->sender->name,
          'receiver_name'=> $msg->receiver->name,
          'time'=>$msg->updated_at,
         
        ];

    }
}
