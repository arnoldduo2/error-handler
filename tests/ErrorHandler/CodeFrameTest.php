<?php

declare(strict_types=1);

namespace Anode\ErrorHandler\Tests;

use Anode\ErrorHandler\CodeFrame;
use Anode\ErrorHandler\Editor;
use PHPUnit\Framework\TestCase;

class CodeFrameTest extends TestCase
{
   private string $dir;
   private string $file;

   protected function setUp(): void
   {
      $this->dir = sys_get_temp_dir() . '/anode-eh-frame-' . uniqid();
      mkdir($this->dir);
      $this->file = $this->dir . '/Items.php';
      file_put_contents($this->file, implode("\n", [
         '<?php',                                                    // 1
         '',                                                         // 2
         'function show($items, $obj)',                              // 3
         '{',                                                        // 4
         '    $total = 0;',                                          // 5
         '    foreach ($items as $i) {',                             // 6
         '        $total += $i[\'qty\'] * $price;',                  // 7
         '    }',                                                    // 8
         '    return $total / 0 + $obj->missing() + undefined_fn();', // 9
         '}',                                                        // 10
      ]));
   }

   protected function tearDown(): void
   {
      TestFiles::remove($this->dir);
   }

   public function testLinesAroundTheErrorAreKeyedByLineNumber(): void
   {
      $frame = CodeFrame::lines($this->file, 7, 2);
      $this->assertSame(5, $frame['start']);
      $this->assertSame(7, $frame['error']);
      $this->assertSame([5, 6, 7, 8, 9], array_keys($frame['lines']));
      $this->assertStringContainsString('$price', $frame['lines'][7]);
   }

   public function testUnreadableOrMissingSourceGivesNull(): void
   {
      $this->assertNull(CodeFrame::lines($this->dir . '/nope.php', 3));
      $this->assertNull(CodeFrame::lines($this->file, 999));
      $this->assertNull(CodeFrame::lines($this->file, 0));
      $this->assertStringContainsString('not available', CodeFrame::html($this->dir . '/nope.php', 3));
      $this->assertSame('', CodeFrame::text($this->dir . '/nope.php', 3));
   }

   /** @dataProvider messages */
   public function testTheUnderlinedPartComesFromTheMessage(string $message, int $line, string $expected): void
   {
      $text = explode("\n", (string) file_get_contents($this->file))[$line - 1];
      $focus = CodeFrame::focus($message, $text);
      $this->assertNotNull($focus, $message);
      $this->assertSame($expected, substr($text, $focus[0], $focus[1]));
   }

   public static function messages(): array
   {
      return [
         'variable' => ['Undefined variable $price', 7, '$price'],
         'function' => ['Call to undefined function undefined_fn()', 9, 'undefined_fn'],
         'namespaced function' => ['Call to undefined function App\\Support\\undefined_fn()', 9, 'undefined_fn'],
         'method' => ['Call to undefined method stdClass::missing()', 9, 'missing'],
         'member function on null' => ['Call to a member function missing() on null', 9, 'missing'],
         'division' => ['Division by zero', 9, '/'],
         'array key' => ['Undefined array key "qty"', 7, "['qty']"],
         'property' => ['Undefined property: stdClass::$missing', 9, 'missing'],
      ];
   }

   public function testWithoutAClueTheWholeStatementIsUnderlined(): void
   {
      $text = '        $total += $i[\'qty\'] * 2;';
      $focus = CodeFrame::focus('something odd', $text);
      $this->assertSame('$total += $i[\'qty\'] * 2;', substr($text, $focus[0], $focus[1]));
      $this->assertNull(CodeFrame::focus('x', '    // just a comment'));
   }

   public function testHtmlMarksTheFailingLineAndUnderlinesThePart(): void
   {
      $html = CodeFrame::html($this->file, 7, 'Undefined variable $price', 2);
      $this->assertStringContainsString('class="cl cl-error"', $html);
      $this->assertSame(1, substr_count($html, 'cl-error'));
      $this->assertMatchesRegularExpression('/<span class="tv tu">\$price<\/span>/', $html);
      $this->assertStringContainsString('<span class="tk">foreach</span>', $html, 'PHP is coloured');
      $this->assertStringContainsString('<span class="cn">7</span>', $html);
      $this->assertStringNotContainsString('<script', CodeFrame::html($this->file, 7));
   }

   public function testSourceIsEscaped(): void
   {
      file_put_contents($this->file, "<?php\n\$a = '<script>alert(1)</script>';\n");
      $html = CodeFrame::html($this->file, 2, 'x');
      $this->assertStringNotContainsString('<script>', $html);
      $this->assertStringContainsString('&lt;script&gt;', $html);
   }

   public function testTextFrameHasLineNumbersAnArrowAndCarets(): void
   {
      $text = CodeFrame::text($this->file, 7, 'Undefined variable $price', 2);
      $lines = explode("\n", $text);
      $this->assertStringStartsWith('   5 | ', $lines[0]);
      $this->assertStringStartsWith(' > 7 | ', $lines[2]);
      $this->assertStringContainsString('^^^^^^', $lines[3]);
      $this->assertSame(strpos($lines[2], '$price'), strpos($lines[3], '^'), 'the carets sit under the variable');
   }

   public function testEditorLinks(): void
   {
      $this->assertSame('vscode://file/' . ltrim(str_replace(' ', '%20', $this->file), '/') === '' ? '' : 'vscode://file/' . implode('/', array_map('rawurlencode', explode('/', $this->file))) . ':7:1', Editor::url($this->file, 7));
      $this->assertStringStartsWith('cursor://file/', Editor::url($this->file, 7, 'cursor'));
      $this->assertStringContainsString('line=7', Editor::url($this->file, 7, 'phpstorm'));
      $this->assertSame('my://open?path=' . implode('/', array_map('rawurlencode', explode('/', $this->file))) . '&l=7', Editor::url($this->file, 7, 'my://open?path={file}&l={line}'));
      $this->assertNull(Editor::url($this->file, 7, 'none'));
      $this->assertNull(Editor::url($this->dir . '/nope.php', 7), 'no link for a file that does not exist');
   }

   public function testEditorPathMapForDockerAndWsl(): void
   {
      $url = Editor::url($this->file, 7, 'vscode', [$this->dir => 'C:\\xampp\\htdocs\\app']);
      $this->assertSame('vscode://file/C:/xampp/htdocs/app/Items.php:7:1', $url);
   }
}
