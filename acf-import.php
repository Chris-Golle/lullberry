<?php
/**
 * Lullberry ACF category data import
 * Run with: wp eval-file ~/Documents/Lullberry/acf-import.php
 */

$strengths = [

    'calm' => [
        'cat_questions' => '<span>Do you</span>let things unfold and trust the process?<br/><span>Do you</span>come refreshed after a moment of contemplation?',
        'cat_highlight' => 'calm',
        'cat_etymology' => 'from Ancient Greek καῦμα which describes the <span>warmth from the sun</span><br/>(noun) <span class="highlight">the state of a mind at peace, untouched by fear</span>',
        'cat_quote'     => 'Trees that are slow to grow bear the best fruit.<cite>Molière</cite>',
        'cat_traits'    => "let your mind settle, like a glass of muddy water\nstand by a tree\nobserve what happens when you let go of hurry\nwatch your eggs hatch in their own time\nbe vulnerable\nmake chaos a sandbox where you invite calm to play\nand never hesitate to use this strength: it grows when you use it!",
    ],

    'compassion' => [
        'cat_questions' => '<span>Do you</span>smile when you see smiles?<br/><span>Do you</span>sometimes bring people what they need before they asked?',
        'cat_highlight' => 'compassion',
        'cat_etymology' => 'from Latin com-pati which means <span>to suffer together with</span><br/>(noun) <span class="highlight">the ability to see all living things as family members</span>',
        'cat_quote'     => 'Instead of putting others in their place, put yourself in their place<cite>Amish proverb</cite>',
        'cat_traits'    => "this person you had in mind, tell them what they mean to you\nstart with compassion for yourself\nbe gentle\nbe curious about the joys and pains of all living things\ntears can heal\nsee what happens when you are no longer at the center of your world\nand never hesitate to use this strength: it grows when you use it!",
    ],

    'courage' => [
        'cat_questions' => '<span>Can you</span>put comfort in the back seat when a new adventure takes the wheel?<br/><span>Were you</span>born saying <i>why not</i>?',
        'cat_highlight' => 'courage',
        'cat_etymology' => 'from Latin cor which means the <span>heart</span><br/>(noun) <span class="highlight">the ability to tame your fears</span>',
        'cat_quote'     => "Life shrinks or expands in proportion to one's courage<cite>Anais Nin</cite>",
        'cat_traits'    => "ask why not\nspeak with courage, listen with courage\nfail\nsay <i>I don't know</i>\npick a dream and be relentless\nget quality sleep, tomorrow is a new day\nand never hesitate to use this strength: it grows when you use it!",
    ],

    'creativity' => [
        'cat_questions' => '<span>Do you</span>see life as a canvas?<br/><span>Do you</span>surprise yourself with what your hands can do?',
        'cat_highlight' => 'creativity',
        'cat_etymology' => 'from Medieval Latin creātīvus which means <span>to give birth</span><br/>(noun) <span class="highlight">the ability to bring new things into existence</span>',
        'cat_quote'     => 'Creativity is inventing, experimenting, growing, taking risks, breaking rules, making mistakes, and having fun.<cite>Mary Lou Cook</cite>',
        'cat_traits'    => "this thing you had in mind, do it\nhit pause on common sense\nlet your mind have fun, bring your body along (or vice versa)\nmake real what you can imagine\ndon't wait for the perfect moment\nrediscover how a child sees the world\nand never hesitate to use this strength: it grows when you use it!",
    ],

    'curiosity' => [
        'cat_questions' => '<span>Do you</span>see the world as a playground?<br/><span>Do you</span>like to discover and sometimes find out that you were wrong?',
        'cat_highlight' => 'curiosity',
        'cat_etymology' => 'from Latin cūra which means <span>to care</span><br/>(noun) <span class="highlight">the desire to learn</span>',
        'cat_quote'     => 'Be curious. Read widely. Try new things. What people call intelligence just boils down to curiosity.<cite>Aaron Swartz</cite>',
        'cat_traits'    => "this thing you had in mind, do it\nask why\nask why not\nlet curiosity lead you to new passions\nbe like a child\ntreat your curious mind like you treat your hungry belly\nand never hesitate to use this strength: it grows when you use it!",
    ],

    'discipline' => [
        'cat_questions' => '<span>Are you</span>good with promises?<br/><span>Do you</span>find ticking things off a to-do list sexy?',
        'cat_highlight' => 'discipline',
        'cat_etymology' => 'from Indo-European dek which means <span>to move towards acceptance</span><br/>(noun) <span class="highlight">the ability to hear what our future self is saying</span>',
        'cat_quote'     => 'The future depends on what we do in the present.<cite>Mahatma Gandhi</cite>',
        'cat_traits'    => "this thing you had in mind, do it\nlisten to your future self\ngive yourself a pinch of tough love\ngrab your vision by the feet and plant it on the ground\nfind your way of resting and being lazy sometimes\nremember to celebrate\nand never hesitate to use this strength: it grows when you use it!",
    ],

    'forgiveness' => [
        'cat_questions' => '<span>Do you</span>treasure the lightness that comes after letting go?<br/><span>Would you</span>rather be free than be right?',
        'cat_highlight' => 'forgiveness',
        'cat_etymology' => 'from Old English forġiefan which means <span>to release</span><br/>(noun) <span class="highlight">the ability to let go</span>',
        'cat_quote'     => 'The weak can never forgive. Forgiveness is the attribute of the strong.<cite>Mahatma Gandhi</cite>',
        'cat_traits'    => "this person you had in mind, forgive them and set yourself free\nforgive yourself\nlong for the peace in the words <i>i forgive you</i>\nlearn to really love by learning to really forgive\nforgive in silence: you're healing yourself and, sometimes, no one needs to know\nbe vulnerable\nand never hesitate to use this strength: it grows when you use it!",
    ],

    'gratitude' => [
        'cat_questions' => '<span>Does your</span>heart sing when you meet what is alive, human beings, animals, plants?<br/><span>Can you</span>thank life for both the easy and the tough lessons?',
        'cat_highlight' => 'gratitude',
        'cat_etymology' => 'from Indo-European gʷerH which means <span>to praise</span><br/>(noun) <span class="highlight">the ability to recognize goodness and beauty</span>',
        'cat_quote'     => 'Some people grumble that roses have thorns; I am grateful that thorns have roses.<cite>Alphonse Karr</cite>',
        'cat_traits'    => "this person you had in mind, tell them what they mean to you\ninvite gratitude in your life as if it was a doctor's order\nsee small things as small miracles, big things as big miracles\nno harm will come from choosing to see the good\nlook at the sky\nspeak your gratitude, let it out\nand never hesitate to use this strength: it grows when you use it!",
    ],

    'honesty' => [
        'cat_questions' => '<span>If you</span>had an ingredients label, would it say: no colorings, no sweeteners?<br/><span>Do you</span>gravitate towards fewer but truer friendships?',
        'cat_highlight' => 'honesty',
        'cat_etymology' => 'from Latin honestāre which means <span>to clothe with honour</span><br/>(noun) <span class="highlight">the gift of truth</span>',
        'cat_quote'     => 'Truth is found in simplicity<cite>Isaac Newton</cite>',
        'cat_traits'    => "say no!\nhonour people who disagree\nact when you see a half-truth looking for its other half\ntell it like it is\nbe curious about the true you and share it\nbe vulnerable\nand never hesitate to use this strength: it grows when you use it!",
    ],

    'hope' => [
        'cat_questions' => '<span>Do you</span>vibrate when you hear people speak with hope?<br/><span>Do you</span>ask why not?',
        'cat_highlight' => 'hope',
        'cat_etymology' => 'from Indo-European kēwp which refers to <span>rising vapor</span><br/>(noun) <span class="highlight">the ability to trust in what will come</span>',
        'cat_quote'     => 'Follow your bliss and the universe will open doors for you where there were only walls.<cite>Joseph Campbell</cite>',
        'cat_traits'    => "this thing you had in mind, do it\nadd a spoonful of hope in every decision you bake\nrecognize that great wines come from struggling vines\nhope with others like you laugh with others\nfind one block that stands between you and hope, remove it\nsay wow!\nand never hesitate to use this strength: it grows when you use it!",
    ],

    'humility' => [
        'cat_questions' => '<span>Do you</span>offer your vulnerability as readily as your smile?<br/><span>Do you</span>enjoy the taste of unassuming truth?',
        'cat_highlight' => 'humility',
        'cat_etymology' => 'from Latin humus which means the <span>earth</span><br/>(noun) <span class="highlight">the ability to be comfortable with reality</span>',
        'cat_quote'     => 'Humility is nothing but truth<cite>St. Vincent de Paul</cite>',
        'cat_traits'    => "stay teachable, even when you know\nthink of yourself a little less — but don't think less of yourself\npraise humility when you see it in others\nlook for what is right, not for who is right\nknow your strengths\nsee life as a mysterious, precious lesson in humility\nand never hesitate to use this strength: it grows when you use it!",
    ],

    'humor' => [
        'cat_questions' => '<span>Do you</span>enjoy playing with children?<br/><span>Do you</span>forget the other animals once you\'ve reached the monkeys at the zoo?',
        'cat_highlight' => 'humor',
        'cat_etymology' => 'from Old French humor which referred to bodily fluids, and later to <span>whims and caprices</span><br/>(noun) <span class="highlight">the ability to tickle people\'s spirits</span>',
        'cat_quote'     => 'He who laughs, lasts.<cite>Mary Pettibone Poole</cite>',
        'cat_traits'    => "this thing you had in mind, do it upside down\nmake a funny face when people are looking\nthough not on Duolingo, humor is a language that can be practiced\ntry laughing yoga: sit and laugh for no reason\nlet your mishaps transform into funny stories\nwatch cat videos and be inspired\nand never hesitate to use this strength: it grows when you use it!",
    ],

    'joy' => [
        'cat_questions' => '<span>Do you</span>easily let your body decide?<br/><span>Have you</span>found the happy baby inside you?',
        'cat_highlight' => 'joy',
        'cat_etymology' => 'from Latin vīta which means <span>life</span><br/>(noun) <span class="highlight">the impulse to celebrate being alive</span>',
        'cat_quote'     => 'Find what it is that makes you happy and who it is that makes you happy and you\'re set. Promise.<cite>Anonymous</cite>',
        'cat_traits'    => "this thing you had in mind, go for it!\nyour body wants to move: feel how!\nwhen joyful, hug someone who isn't\nfor an hour every day, stop adulting\nspread your light: be on fire!\nsing!\nand never hesitate to use this strength: it grows when you use it!",
    ],

    'kindness' => [
        'cat_questions' => '<span>Are you</span>moved by people in love, by people in need?<br/><span>Do you</span>feel richer when you share?',
        'cat_highlight' => 'kindness',
        'cat_etymology' => 'from Old English cynd which refers to <span>our human nature</span><br/>(noun) <span class="highlight">the ability to let the heart speak</span>',
        'cat_quote'     => 'You can accomplish by kindness what you cannot by force.<cite>Publilius Syrus</cite>',
        'cat_traits'    => "this person you had in mind, tell them what they mean to you\nbe a friend\nbe curious, see how kindness comes back to you in unexplainable ways\nlet kindness heal and guide\nsee every living thing as a chance to be kinder\nbe vulnerable\nand never hesitate to use this strength: it grows when you use it!",
    ],

    'leadership' => [
        'cat_questions' => '<span>Do you</span>have dreams waiting to be shared?<br/><span>Are you</span>someone you would be proud to follow?',
        'cat_highlight' => 'leadership',
        'cat_etymology' => 'from Old German laidijaną which means <span>to set in motion</span><br/>(noun) <span class="highlight">the ability to share your enthusiasm</span>',
        'cat_quote'     => 'Leaders are happy to zig while others zag.<cite>Bill Taylor</cite>',
        'cat_traits'    => "this thing you had in mind, do it\nsee the good\nbe bold and humble\nlet people come to you\nhelp people believe in themselves\ncelebrate\nand never hesitate to use this strength: it grows when you use it!",
    ],

    'love' => [
        'cat_questions' => '<span>Do you</span>feel that you are enough?<br/><span>Is your</span>kindness given with no conditions?',
        'cat_highlight' => 'love',
        'cat_etymology' => 'from Indo-European lewb which means <span>to care for</span><br/>(noun) <span class="highlight">the impulse to care and empower</span>',
        'cat_quote'     => 'You yourself, as much as anybody in the entire universe, deserve your love and affection.<cite>Gautama Buddha</cite>',
        'cat_traits'    => "this person you had in mind, let them know your feelings\nstop all striving and love yourself\nshow when you care\nfind one block that stands between you and love, remove it\nbe vulnerable\nsurround yourself with nature\nand never hesitate to use this strength: it grows when you use it!",
    ],

    'love-of-learning' => [
        'cat_questions' => '<span>Would you</span>choose a new dance move over a familiar one?<br/><span>Can you</span>enjoy finding out that you were wrong?',
        'cat_highlight' => 'love of learning',
        'cat_etymology' => 'from Indo-European leys which means <span>to follow a track</span><br/>(noun) <span class="highlight">the excitement that comes with growth</span>',
        'cat_quote'     => 'It is the learners who inherit the future.<cite>Eric Hoffer</cite>',
        'cat_traits'    => "this thing you had in mind, learn it\nask why\nask why not\nmeasure and celebrate your progress\nquestion what you know, make space for the new\nlisten\nand never hesitate to use this strength: it grows when you use it!",
    ],

    'spirituality' => [
        'cat_questions' => '<span>Do you</span>sometimes know deeply that all is ok?<br/><span>Does</span>lighting a candle change your mood?',
        'cat_highlight' => 'spirituality',
        'cat_etymology' => 'from Latin spīrō which means <span>I breathe</span><br/>(noun) <span class="highlight">the longing for one\'s true nature</span>',
        'cat_quote'     => 'Smile, breathe and go slowly.<cite>Thich Nhat Hanh</cite>',
        'cat_traits'    => "this thing you know would transform you, do it\nbe vulnerable\nquestion what you think you know\nstop what you're doing, breathe\nfeel\ngaze into your own eyes\nand never hesitate to use this strength: it grows when you use it!",
    ],

    'team-spirit' => [
        'cat_questions' => '<span>Is your</span>box of chocolates tastier when it is shared?<br/><span>Do you</span>thank your limitations for the opportunity to seek help?',
        'cat_highlight' => 'team spirit',
        'cat_etymology' => 'from Old German teuhaną which refers to elements <span>bound together</span><br/>(noun) <span class="highlight">the ability to depend on others</span>',
        'cat_quote'     => 'No one can whistle a symphony.<cite>H.E. Luccock</cite>',
        'cat_traits'    => "this person you had in mind, tell them what they mean to you\nspread your warmth and your energy: be on fire\ndream up projects that are too big for one person\nsing\nbring your community around a fire, the way your ancestors did\nbe vulnerable\nand never hesitate to use this strength: it grows when you use it!",
    ],

    'wisdom' => [
        'cat_questions' => '<span>Is your</span>mind malleable like clay?<br/><span>Do you</span>thank your wounds for what they\'ve made you?',
        'cat_highlight' => 'wisdom',
        'cat_etymology' => 'from Indo-European weyd which means <span>to see</span><br/>(noun) <span class="highlight">the ability to know what is important</span>',
        'cat_quote'     => 'Never did nature say one thing and wisdom say another.<cite>Edmund Burke</cite>',
        'cat_traits'    => "this thing you know could transform you, do it\nobserve nature\nremember to be a child\nask questions that have no answers\nbe vulnerable\nlet go and let go again\nand never hesitate to use this strength: it grows when you use it!",
    ],

];

foreach ( $strengths as $slug => $fields ) {
    $term = get_term_by( 'slug', $slug, 'product_cat' );
    if ( ! $term ) {
        echo "NOT FOUND: $slug\n";
        continue;
    }
    foreach ( $fields as $key => $value ) {
        update_term_meta( $term->term_id, $key, $value );
    }
    echo "Updated: $slug (term_id={$term->term_id})\n";
}

echo "\nDone.\n";
