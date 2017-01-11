<?php

namespace Basic\CmsBundle\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Lexer;


class Round extends FunctionNode
{
    // (1)

    public $firstNumberExpression = null;
//    public $secondDateExpression = null;

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER); // (2)

        $parser->match(Lexer::T_OPEN_PARENTHESIS); // (3)
        $this->firstNumberExpression = $parser->ArithmeticPrimary(); // (4)
        //$parser->match(Lexer::T_COMMA); // (5)
        //$this->secondDateExpression = $parser->ArithmeticPrimary(); // (6)
        $parser->match(Lexer::T_CLOSE_PARENTHESIS); // (3)
    }

    public function getSql(SqlWalker $sqlWalker)
    {
        return 'ROUND(' .
            $this->firstNumberExpression->dispatch($sqlWalker) .
//            $this->secondDateExpression->dispatch($sqlWalker) .
            ')'; // (7)
    }
}