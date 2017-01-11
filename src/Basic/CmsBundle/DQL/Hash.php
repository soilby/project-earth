<?php

namespace Basic\CmsBundle\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Lexer;


class Hash extends FunctionNode
{
    // (1)

    public $firstStringExpression = null;

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER); // (2)

        $parser->match(Lexer::T_OPEN_PARENTHESIS); // (3)
        $this->firstStringExpression = $parser->StringPrimary(); // (4)
        $parser->match(Lexer::T_CLOSE_PARENTHESIS); // (3)
    }

    public function getSql(SqlWalker $sqlWalker)
    {
        return 'MD5(' .
            $this->firstStringExpression->dispatch($sqlWalker) .
            ')'; // (7)
    }
}