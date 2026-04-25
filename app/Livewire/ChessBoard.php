<?php

namespace App\Livewire;

use Livewire\Component;
use Ryanhs\Chess\Chess;

class ChessBoard extends Component
{
    public $fen = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
    public $selectedSquare = null;
    public $legalMoves = [];
    public $moveHistory = [];
    public $capturedWhite = [];
    public $capturedBlack = [];
    public $turn = 'w';
    public $status = 'Playing';

    public function mount()
    {
        $this->updateGameState();
    }

    public function selectSquare($square)
    {
        $chess = new Chess();
        $chess->load($this->fen);

        if ($this->selectedSquare === $square) {
            $this->selectedSquare = null;
            $this->legalMoves = [];
            return;
        }

        if ($this->selectedSquare) {
            // Check if this is a legal move
            $move = [
                'from' => $this->selectedSquare,
                'to' => $square,
                'promotion' => 'q'
            ];

            $result = $chess->move($move);

            if ($result) {
                $this->fen = $chess->fen();
                $this->selectedSquare = null;
                $this->legalMoves = [];
                $this->updateGameState();
                return;
            }
        }

        $piece = $chess->get($square);
        if ($piece && $piece['color'] === $this->turn) {
            $this->selectedSquare = $square;
            $moves = $chess->moves(['square' => $square, 'verbose' => true]);
            $this->legalMoves = collect($moves)->keyBy('to')->toArray();
        } else {
            $this->selectedSquare = null;
            $this->legalMoves = [];
        }
    }

    private function updateGameState()
    {
        $chess = new Chess();
        $chess->load($this->fen);
        
        $this->turn = $chess->turn();
        $this->moveHistory = $chess->history();
        
        if ($chess->inCheckmate()) {
            $this->status = 'Checkmate! ' . ($this->turn === 'w' ? 'Black' : 'White') . ' wins.';
        } elseif ($chess->inDraw() || $chess->inStalemate() || $chess->inThreefoldRepetition()) {
            $this->status = 'Draw!';
        } elseif ($chess->inCheck()) {
            $this->status = 'Check!';
        } else {
            $this->status = 'Playing';
        }

        $this->calculateCaptured($chess);
    }

    private function calculateCaptured(Chess $chess)
    {
        $startingPieces = [
            'p' => 8, 'n' => 2, 'b' => 2, 'r' => 2, 'q' => 1,
            'P' => 8, 'N' => 2, 'B' => 2, 'R' => 2, 'Q' => 1,
        ];

        $currentPieces = [
            'p' => 0, 'n' => 0, 'b' => 0, 'r' => 0, 'q' => 0,
            'P' => 0, 'N' => 0, 'B' => 0, 'R' => 0, 'Q' => 0,
        ];

        $board = $chess->board();
        foreach ($board as $row) {
            foreach ($row as $piece) {
                if ($piece) {
                    $key = $piece['color'] === 'w' ? strtoupper($piece['type']) : $piece['type'];
                    $currentPieces[$key]++;
                }
            }
        }

        $this->capturedWhite = [];
        $this->capturedBlack = [];

        foreach (['p', 'n', 'b', 'r', 'q'] as $type) {
            $diffBlack = $startingPieces[$type] - $currentPieces[$type];
            for ($i = 0; $i < $diffBlack; $i++) {
                $this->capturedWhite[] = $type;
            }

            $typeUpper = strtoupper($type);
            $diffWhite = $startingPieces[$typeUpper] - $currentPieces[$typeUpper];
            for ($i = 0; $i < $diffWhite; $i++) {
                $this->capturedBlack[] = $typeUpper;
            }
        }
    }

    public function render()
    {
        $chess = new Chess();
        $chess->load($this->fen);
        $board = $chess->board();

        $ranks = [8, 7, 6, 5, 4, 3, 2, 1];
        $files = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];

        $squares = [];
        for ($r = 0; $r < 8; $r++) {
            for ($f = 0; $f < 8; $f++) {
                $squareName = $files[$f] . $ranks[$r];
                $squares[$squareName] = [
                    'piece' => $board[$r][$f],
                    'isDark' => ($r + $f) % 2 !== 0,
                ];
            }
        }

        return view('livewire.chess-board', [
            'squares' => $squares
        ]);
    }
}
