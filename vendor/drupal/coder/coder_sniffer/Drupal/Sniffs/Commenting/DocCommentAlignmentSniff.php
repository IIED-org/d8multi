<?php
/**
 * \Drupal\Sniffs\Commenting\DocCommentAlignmentSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace Drupal\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Largely copied from
 * \PHP_CodeSniffer\Standards\Squiz\Sniffs\Commenting\DocCommentAlignmentSniff to also
 * handle the "var" keyword. See
 * https://github.com/squizlabs/PHP_CodeSniffer/pull/1212
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DocCommentAlignmentSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [T_DOC_COMMENT_OPEN_TAG];

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // We are only interested in function/class/interface doc block comments.
        $ignore = Tokens::$emptyTokens;

        $nextToken = $phpcsFile->findNext($ignore, ($stackPtr + 1), null, true);
        $ignore    = [
            T_CLASS     => true,
            T_INTERFACE => true,
            T_FUNCTION  => true,
            T_PUBLIC    => true,
            T_PRIVATE   => true,
            T_PROTECTED => true,
            T_STATIC    => true,
            T_ABSTRACT  => true,
            T_PROPERTY  => true,
            T_OBJECT    => true,
            T_PROTOTYPE => true,
            T_VAR       => true,
        ];

        if (isset($ignore[$tokens[$nextToken]['code']]) === false) {
            // Could be a file comment.
            $prevToken = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
            if ($tokens[$prevToken]['code'] !== T_OPEN_TAG) {
                return;
            }
        }

        // There must be one space after each star (unless it is an empty comment line)
        // and all the stars must be aligned correctly.
        $requiredColumn = ($tokens[$stackPtr]['column'] + 1);
        $endComment     = $tokens[$stackPtr]['comment_closer'];
        for ($i = ($stackPtr + 1); $i <= $endComment; $i++) {
            if ($tokens[$i]['code'] !== T_DOC_COMMENT_STAR
                && $tokens[$i]['code'] !== T_DOC_COMMENT_CLOSE_TAG
            ) {
                continue;
            }

            if ($tokens[$i]['code'] === T_DOC_COMMENT_CLOSE_TAG) {
                // Can't process the close tag if it is not the first thing on the line.
                $prev = $phpcsFile->findPrevious(T_DOC_COMMENT_WHITESPACE, ($i - 1), $stackPtr, true);
                if ($tokens[$prev]['line'] === $tokens[$i]['line']) {
                    continue;
                }
            }

            if ($tokens[$i]['column'] !== $requiredColumn) {
                $error = 'Expected %s space(s) before asterisk; %s found';
                $data  = [
                    ($requiredColumn - 1),
                    ($tokens[$i]['column'] - 1),
                ];
                $fix   = $phpcsFile->addFixableError($error, $i, 'SpaceBeforeStar', $data);
                if ($fix === true) {
                    $padding = str_repeat(' ', ($requiredColumn - 1));
                    if ($tokens[$i]['column'] === 1) {
                        $phpcsFile->fixer->addContentBefore($i, $padding);
                    } else {
                        $phpcsFile->fixer->replaceToken(($i - 1), $padding);
                    }
                }
            }

            if ($tokens[$i]['code'] !== T_DOC_COMMENT_STAR) {
                continue;
            }

            if ($tokens[($i + 2)]['line'] !== $tokens[$i]['line']) {
                // Line is empty.
                continue;
            }

            if ($tokens[($i + 1)]['code'] !== T_DOC_COMMENT_WHITESPACE) {
                $error = 'Expected 1 space after asterisk; 0 found';
                $fix   = $phpcsFile->addFixableError($error, $i, 'NoSpaceAfterStar');
                if ($fix === true) {
                    $phpcsFile->fixer->addContent($i, ' ');
                }
            } else if ($tokens[($i + 2)]['code'] === T_DOC_COMMENT_TAG
                && $tokens[($i + 1)]['content'] !== ' '
                // Special @code/@endcode/@see tags can have more than 1 space.
                && in_array(
                    $tokens[($i + 2)]['content'],
                    [
                        '@param',
                        '@return',
                        '@throws',
                        '@ingroup',
                        '@var',
                    ]
                ) === true
            ) {
                $error = 'Expected 1 space after asterisk; %s found';
                $data  = [strlen($tokens[($i + 1)]['content'])];
                $fix   = $phpcsFile->addFixableError($error, $i, 'SpaceAfterStar', $data);
                if ($fix === true) {
                    $phpcsFile->fixer->replaceToken(($i + 1), ' ');
                }
            }//end if
        }//end for

    }//end process()


}//end class
