<?php

declare(strict_types=1);

namespace App\Tools\PhpCsFixer;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\IndentationTrait;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

final class SingleLineShortMethodSignatureFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    use IndentationTrait;

    private const MAX_SIGNATURE_LENGTH = 180;

    private const METHOD_MODIFIER_TOKENS = [
        \T_PUBLIC,
        \T_PROTECTED,
        \T_PRIVATE,
        \T_ABSTRACT,
        \T_FINAL,
        \T_STATIC,
    ];

    public function getName(): string
    {
        return 'App/single_line_short_method_signature';
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Method signature is collapsed to a single line when short enough and opening brace is always placed on a new line.',
            [
                new CodeSample(
                    <<<'PHP'
                        <?php

                        final class Example
                        {
                            public function __construct(
                                private readonly string $name,
                                private readonly int $age,
                            ): void {
                            }
                        }
                        PHP,
                ),
            ],
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(\T_FUNCTION);
    }

    /**
     * {@inheritdoc}
     *
     * Must run after BracesPositionFixer and FunctionDeclarationFixer.
     */
    public function getPriority(): int
    {
        return -10;
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
    {
        $tokensAnalyzer = new TokensAnalyzer($tokens);
        $methodIndexes = [];

        foreach ($tokensAnalyzer->getClassyElements() as $index => $element) {
            if ('method' === $element['type']) {
                $methodIndexes[] = $index;
            }
        }

        rsort($methodIndexes);

        foreach ($methodIndexes as $functionIndex) {
            $startParenthesisIndex = $tokens->getNextTokenOfKind($functionIndex, ['(', ';']);

            if (!$tokens[$startParenthesisIndex]->equals('(')) {
                continue;
            }

            $endParenthesisIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $startParenthesisIndex);
            $openBraceIndex = $tokens->getNextTokenOfKind($endParenthesisIndex, ['{', ';', [CT::T_PROPERTY_HOOK_BRACE_OPEN]]);

            if (!$tokens[$openBraceIndex]->equals('{')) {
                continue;
            }

            $signatureStartIndex = $this->findSignatureStartIndex($tokens, $functionIndex);
            $signatureEndIndex = $tokens->getPrevMeaningfulToken($openBraceIndex);

            if (
                !$tokens->isPartialCodeMultiline($signatureStartIndex, $signatureEndIndex)
                || !$this->hasCollapsibleLength($tokens, $signatureStartIndex, $signatureEndIndex)
                || $this->containsComments($tokens, $signatureStartIndex, $signatureEndIndex)
            ) {
                $this->ensureBraceOnNextLine($tokens, $openBraceIndex, $signatureStartIndex);
                continue;
            }

            $this->collapseMultilineWhitespaces($tokens, $signatureStartIndex, $signatureEndIndex);
            $this->normalizeSignatureSpacing($tokens, $signatureStartIndex, $signatureEndIndex, $startParenthesisIndex, $endParenthesisIndex);

            $openBraceIndex = $tokens->getNextTokenOfKind($endParenthesisIndex, ['{', ';', [CT::T_PROPERTY_HOOK_BRACE_OPEN]]);
            if ($tokens[$openBraceIndex]->equals('{')) {
                $this->ensureBraceOnNextLine($tokens, $openBraceIndex, $signatureStartIndex);
            }
        }
    }

    private function findSignatureStartIndex(Tokens $tokens, int $functionIndex): int
    {
        $signatureStartIndex = $functionIndex;

        while (true) {
            $previousMeaningfulIndex = $tokens->getPrevMeaningfulToken($signatureStartIndex);

            if (null === $previousMeaningfulIndex || !$tokens[$previousMeaningfulIndex]->isGivenKind(self::METHOD_MODIFIER_TOKENS)) {
                return $signatureStartIndex;
            }

            $signatureStartIndex = $previousMeaningfulIndex;
        }
    }

    private function hasCollapsibleLength(Tokens $tokens, int $signatureStartIndex, int $signatureEndIndex): bool
    {
        $flattenedSignature = '';

        for ($index = $signatureStartIndex; $index <= $signatureEndIndex; ++$index) {
            $token = $tokens[$index];

            if ($token->isWhitespace()) {
                $flattenedSignature .= str_contains($token->getContent(), "\n") ? ' ' : $token->getContent();
                continue;
            }

            $flattenedSignature .= $token->getContent();
        }

        $lineLength = \strlen($this->getLineIndentation($tokens, $signatureStartIndex) . trim($flattenedSignature));

        return $lineLength < self::MAX_SIGNATURE_LENGTH;
    }

    private function containsComments(Tokens $tokens, int $signatureStartIndex, int $signatureEndIndex): bool
    {
        for ($index = $signatureStartIndex; $index <= $signatureEndIndex; ++$index) {
            if ($tokens[$index]->isComment()) {
                return true;
            }
        }

        return false;
    }

    private function collapseMultilineWhitespaces(Tokens $tokens, int $signatureStartIndex, int $signatureEndIndex): void
    {
        for ($index = $signatureStartIndex; $index <= $signatureEndIndex; ++$index) {
            $token = $tokens[$index];

            if ($token->isWhitespace()) {
                $tokens[$index] = new Token([\T_WHITESPACE, ' ']);
            }
        }

        for ($index = $signatureStartIndex + 1; $index <= $signatureEndIndex; ++$index) {
            if ($tokens[$index]->isWhitespace() && $tokens[$index - 1]->isWhitespace()) {
                $tokens->clearAt($index);
            }
        }
    }

    private function normalizeSignatureSpacing(
        Tokens $tokens,
        int $signatureStartIndex,
        int $signatureEndIndex,
        int $startParenthesisIndex,
        int $endParenthesisIndex,
    ): void {
        if ($tokens[$startParenthesisIndex - 1]->isWhitespace()) {
            $tokens->clearAt($startParenthesisIndex - 1);
        }

        if ($tokens[$startParenthesisIndex + 1]->isWhitespace()) {
            $tokens->clearAt($startParenthesisIndex + 1);
        }

        if ($tokens[$endParenthesisIndex - 1]->isWhitespace()) {
            $tokens->clearAt($endParenthesisIndex - 1);
        }

        $lastParameterTokenIndex = $tokens->getPrevMeaningfulToken($endParenthesisIndex);
        if (null !== $lastParameterTokenIndex && $tokens[$lastParameterTokenIndex]->equals(',')) {
            $tokens->clearTokenAndMergeSurroundingWhitespace($lastParameterTokenIndex);
        }

        for ($index = $signatureStartIndex; $index <= $signatureEndIndex; ++$index) {
            if (!$tokens[$index]->equals('(') && !$tokens[$index]->equals(')')) {
                continue;
            }

            if ($tokens[$index]->equals('(') && $tokens[$index + 1]->isWhitespace()) {
                $tokens->clearAt($index + 1);
            }

            if ($tokens[$index]->equals(')') && $tokens[$index - 1]->isWhitespace()) {
                $tokens->clearAt($index - 1);
            }
        }

        for ($index = $signatureStartIndex; $index <= $signatureEndIndex; ++$index) {
            if (!$tokens[$index]->isGivenKind([CT::T_TYPE_ALTERNATION, CT::T_TYPE_INTERSECTION])) {
                continue;
            }

            if ($tokens[$index - 1]->isWhitespace()) {
                $tokens->clearAt($index - 1);
            }

            if ($tokens[$index + 1]->isWhitespace()) {
                $tokens->clearAt($index + 1);
            }
        }

        $typeColonIndex = $tokens->getNextMeaningfulToken($endParenthesisIndex);
        if (null === $typeColonIndex || !$tokens[$typeColonIndex]->isGivenKind(CT::T_TYPE_COLON)) {
            return;
        }

        if ($tokens[$typeColonIndex - 1]->isWhitespace()) {
            $tokens->clearAt($typeColonIndex - 1);
        }

        $tokens->ensureWhitespaceAtIndex($typeColonIndex + 1, 0, ' ');

        $nextMeaningfulAfterColon = $tokens->getNextMeaningfulToken($typeColonIndex);
        if (null !== $nextMeaningfulAfterColon) {
            for ($index = $typeColonIndex + 1; $index < $nextMeaningfulAfterColon; ++$index) {
                if ($tokens[$index]->isWhitespace()) {
                    $tokens[$index] = new Token([\T_WHITESPACE, ' ']);
                }
            }
        }
    }

    private function ensureBraceOnNextLine(Tokens $tokens, int $openBraceIndex, int $signatureStartIndex): void
    {
        $indentation = $this->getLineIndentation($tokens, $signatureStartIndex);
        $whitespace = $this->whitespacesConfig->getLineEnding() . $indentation;

        if ($tokens[$openBraceIndex - 1]->isWhitespace()) {
            $tokens[$openBraceIndex - 1] = new Token([\T_WHITESPACE, $whitespace]);
            return;
        }

        $tokens->insertAt($openBraceIndex, new Token([\T_WHITESPACE, $whitespace]));
    }
}
