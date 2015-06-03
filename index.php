<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>phpSentence</title>
    </head>
    <body>
		<?php		
			// Include the autoloader.
			require_once 'classes/autoloader.php';
			
			// This is the test text we're going to use
			$text	= "Hello there, Mr. Smith. What're you doing today... Smith,"
					. " my friend?\n\nI hope it's good. This last sentence will"
					. " cost you $2.50! Just kidding :)";
			
			// Create a new instance
			$Sentence	= new Sentence;
			
			// Split into array of sentences			
			$sentences	= $Sentence->split($text);			
			
			// Count the number of sentences
			$count		= $Sentence->count($text);			
		?>		
		<h1>Sentence example</h1>
		
		<h2>Text parsed</h2>
		<?php echo nl2br($text); ?>

		<h2>Sentences counted</h2>
		<?php echo $count; ?>
		
		<h2>Sentences</h2>
		<ol>
		<?php
			foreach ($sentences as $sentence) {
				echo '<li>'.nl2br($sentence).'</li>';
			}
		?>
		</ol>
    </body>
</html>
