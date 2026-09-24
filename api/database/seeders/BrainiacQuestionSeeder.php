<?php

namespace Database\Seeders;

use App\Models\BrainiacQuestion;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Original practice questions in the numerical / verbal / abstract reasoning
 * style used by cognitive assessments like the PI Learning Indicator — not
 * reproductions of any proprietary test's actual items.
 */
class BrainiacQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            ...$this->numericalWordProblems(),
            ...$this->numberSeries(),
            ...$this->verbalSynonyms(),
            ...$this->verbalAntonyms(),
            ...$this->verbalAnalogies(),
            ...$this->verbalDeductions(),
            ...$this->abstractLetterSequences(),
            ...$this->abstractOddOneOut(),
            ...$this->abstractShapePatterns(),
        ];

        foreach ($questions as $i => $q) {
            $this->validate($q, $i);
        }

        BrainiacQuestion::query()->where('subsection', 'pi_cognitive')->delete();

        foreach ($questions as $q) {
            BrainiacQuestion::create([
                'subsection' => 'pi_cognitive',
                'category' => $q['category'],
                'prompt' => $q['prompt'],
                'options' => $q['options'],
                'correct_option' => $q['correct_option'],
                'explanation' => $q['explanation'] ?? null,
                'difficulty' => $q['difficulty'],
            ]);
        }

        $this->command?->info(count($questions).' Brainiac questions seeded.');
    }

    private function validate(array $q, int $i): void
    {
        $options = $q['options'];
        $strings = array_map(strval(...), $options);

        if (count($options) < 2 || count($options) > 6) {
            throw new RuntimeException("Question #{$i}: options count out of range.");
        }

        if (count(array_unique($strings)) !== count($options)) {
            throw new RuntimeException("Question #{$i}: duplicate option values — {$q['prompt']}");
        }

        if (! array_key_exists($q['correct_option'], $options)) {
            throw new RuntimeException("Question #{$i}: correct_option index out of range — {$q['prompt']}");
        }
    }

    // ---------------------------------------------------------------
    // Numerical reasoning
    // ---------------------------------------------------------------

    private function numericalWordProblems(): array
    {
        $questions = [];

        // Rate: "u units per m minutes" -> amount produced in m2 minutes.
        for ($i = 1; $i <= 15; $i++) {
            $u = 2 + $i;
            $m = 4;
            $multiplier = ($i % 3) + 2; // 2, 3, or 4
            $m2 = $m * $multiplier;
            $correct = $u * $multiplier;

            $questions[] = $this->makeMcq(
                'numerical',
                "A machine produces {$u} parts every {$m} minutes. At that rate, how many parts does it produce in {$m2} minutes?",
                [$correct, ...$this->distinctDistractors($correct)],
                0,
                2,
                "Rate is {$u} parts / {$m} min. Over {$m2} min that's {$u} x {$m2}/{$m} = {$correct} parts.",
            );
        }

        // Percentage increase.
        for ($i = 1; $i <= 15; $i++) {
            $base = 20 * $i + 40; // avoids fractional cents
            $pct = [5, 10, 20, 25, 50][$i % 5];
            $correct = $base + ($base * $pct / 100);

            $questions[] = $this->makeMcq(
                'numerical',
                "A product priced at \${$base} is increased by {$pct}%. What is the new price?",
                [(int) $correct, ...$this->distinctDistractors((int) $correct)],
                0,
                2,
                "{$pct}% of \${$base} is \$".($base * $pct / 100).", so the new price is \${$correct}.",
            );
        }

        // Ratio / proportion.
        for ($i = 1; $i <= 15; $i++) {
            $unitCost = $i + 1;
            $qtyKnown = 3;
            $qtyAsk = $qtyKnown + 2 + ($i % 4);
            $known = $unitCost * $qtyKnown;
            $correct = $unitCost * $qtyAsk;

            $questions[] = $this->makeMcq(
                'numerical',
                "If {$qtyKnown} pens cost \${$known}, how much do {$qtyAsk} pens cost at the same rate?",
                [$correct, ...$this->distinctDistractors($correct)],
                0,
                1,
                "Each pen costs \${$known} / {$qtyKnown} = \${$unitCost}, so {$qtyAsk} pens cost \${$correct}.",
            );
        }

        // Average.
        for ($i = 1; $i <= 15; $i++) {
            $a = 60 + $i;
            $b = 70 + $i;
            $c = 80 + $i;
            $sum = $a + $b + $c;
            $correct = intdiv($sum, 3);

            if ($sum % 3 !== 0) {
                $c++;
                $sum++;
                $correct = intdiv($sum, 3);
            }

            $questions[] = $this->makeMcq(
                'numerical',
                "A student scored {$a}, {$b}, and {$c} on three tests. What is the average score?",
                [$correct, ...$this->distinctDistractors($correct)],
                0,
                1,
                "Average = ({$a} + {$b} + {$c}) / 3 = {$sum} / 3 = {$correct}.",
            );
        }

        // Simple linear equation.
        for ($i = 1; $i <= 15; $i++) {
            $coef = 2 + ($i % 4);
            $x = $i + 1;
            $add = 3 + ($i % 5);
            $result = $coef * $x + $add;

            $questions[] = $this->makeMcq(
                'numerical',
                "If {$coef}x + {$add} = {$result}, what is x?",
                [$x, ...$this->distinctDistractors($x)],
                0,
                2,
                "{$coef}x = {$result} - {$add} = ".($result - $add).', so x = '.($result - $add)." / {$coef} = {$x}.",
            );
        }

        // Speed / distance / time.
        for ($i = 1; $i <= 15; $i++) {
            $speed = 40 + ($i * 5);
            $time = 2 + ($i % 3);
            $distance = $speed * $time;

            $questions[] = $this->makeMcq(
                'numerical',
                "A car travels at {$speed} km/h for {$time} hours. How far does it travel?",
                [$distance, ...$this->distinctDistractors($distance)],
                0,
                1,
                "Distance = speed x time = {$speed} x {$time} = {$distance} km.",
            );
        }

        return $questions;
    }

    private function numberSeries(): array
    {
        $questions = [];

        // Arithmetic progression.
        for ($i = 1; $i <= 10; $i++) {
            $start = $i;
            $diff = 3 + ($i % 4);
            $terms = [$start, $start + $diff, $start + 2 * $diff, $start + 3 * $diff];
            $next = $start + 4 * $diff;

            $questions[] = $this->makeMcq(
                'numerical',
                'What number comes next in the series: '.implode(', ', $terms).', ?',
                [$next, ...$this->distinctDistractors($next)],
                0,
                2,
                "Each term increases by {$diff}, so the next term is {$next}.",
            );
        }

        // Geometric progression.
        for ($i = 1; $i <= 8; $i++) {
            $start = 1 + ($i % 3);
            $ratio = 2 + ($i % 2);
            $terms = [$start, $start * $ratio, $start * $ratio ** 2, $start * $ratio ** 3];
            $next = $start * $ratio ** 4;

            $questions[] = $this->makeMcq(
                'numerical',
                'What number comes next in the series: '.implode(', ', $terms).', ?',
                [$next, ...$this->distinctDistractors($next)],
                0,
                3,
                "Each term is multiplied by {$ratio}, so the next term is {$next}.",
            );
        }

        // Quadratic (n^2 + c).
        for ($i = 1; $i <= 8; $i++) {
            $c = $i;
            $terms = [1 ** 2 + $c, 2 ** 2 + $c, 3 ** 2 + $c, 4 ** 2 + $c];
            $next = 5 ** 2 + $c;

            $questions[] = $this->makeMcq(
                'numerical',
                'What number comes next in the series: '.implode(', ', $terms).', ?',
                [$next, ...$this->distinctDistractors($next)],
                0,
                4,
                "Each term is n\u{00b2} + {$c} for n = 1, 2, 3... so the next term (n=5) is 25 + {$c} = {$next}.",
            );
        }

        // Alternating add/subtract.
        for ($i = 1; $i <= 6; $i++) {
            $start = 50 + $i;
            $add = 10 + $i;
            $sub = 4 + $i;
            $t1 = $start + $add;
            $t2 = $t1 - $sub;
            $t3 = $t2 + $add;
            $next = $t3 - $sub;

            $questions[] = $this->makeMcq(
                'numerical',
                "What number comes next in the series: {$start}, {$t1}, {$t2}, {$t3}, ?",
                [$next, ...$this->distinctDistractors($next)],
                0,
                4,
                "The series alternates +{$add} then -{$sub}. After {$t3}, subtract {$sub} to get {$next}.",
            );
        }

        return $questions;
    }

    // ---------------------------------------------------------------
    // Verbal reasoning
    // ---------------------------------------------------------------

    private function verbalSynonyms(): array
    {
        $sets = [
            ['CANDID', 'frank', ['secretive', 'confused', 'hostile']],
            ['MERIT', 'worth', ['fault', 'weakness', 'excuse']],
            ['SCARCE', 'limited', ['plentiful', 'expensive', 'broken']],
            ['DILIGENT', 'hardworking', ['lazy', 'careless', 'timid']],
            ['ADVERSE', 'unfavorable', ['helpful', 'neutral', 'generous']],
            ['CONCEAL', 'hide', ['reveal', 'discard', 'repair']],
            ['BENEVOLENT', 'kind', ['cruel', 'stubborn', 'anxious']],
            ['FRUGAL', 'thrifty', ['wasteful', 'wealthy', 'generous']],
            ['ELOQUENT', 'articulate', ['clumsy', 'silent', 'confused']],
            ['TRANSIENT', 'temporary', ['permanent', 'solid', 'ancient']],
            ['AMBIGUOUS', 'unclear', ['obvious', 'accurate', 'loud']],
            ['TENACIOUS', 'persistent', ['careless', 'timid', 'forgetful']],
            ['PLAUSIBLE', 'believable', ['impossible', 'silly', 'illegal']],
            ['RETICENT', 'reserved', ['talkative', 'aggressive', 'joyful']],
            ['SUBSTANTIAL', 'considerable', ['tiny', 'fake', 'brief']],
            ['CANDOR', 'honesty', ['deception', 'anger', 'fear']],
            ['MITIGATE', 'lessen', ['worsen', 'ignore', 'reveal']],
            ['PRUDENT', 'sensible', ['reckless', 'silly', 'weak']],
            ['ARDUOUS', 'difficult', ['simple', 'quick', 'pleasant']],
            ['LUCID', 'clear', ['confusing', 'dark', 'loud']],
            ['OBSOLETE', 'outdated', ['modern', 'popular', 'expensive']],
            ['SKEPTIC', 'doubter', ['believer', 'leader', 'liar']],
            ['VERBOSE', 'wordy', ['concise', 'silent', 'polite']],
            ['ZEALOUS', 'enthusiastic', ['indifferent', 'tired', 'shy']],
            ['CANDIDATE', 'nominee', ['winner', 'voter', 'judge']],
            ['DEPLETE', 'exhaust', ['replenish', 'ignore', 'create']],
            ['INNATE', 'inborn', ['learned', 'foreign', 'broken']],
            ['METICULOUS', 'careful', ['sloppy', 'lazy', 'fast']],
            ['OBSTINATE', 'stubborn', ['flexible', 'kind', 'weak']],
            ['RESILIENT', 'tough', ['fragile', 'shy', 'slow']],
            ['SPARSE', 'thin', ['dense', 'heavy', 'wide']],
            ['VOLATILE', 'unstable', ['steady', 'calm', 'safe']],
            ['WARY', 'cautious', ['careless', 'bold', 'trusting']],
            ['YIELD', 'produce', ['destroy', 'stop', 'hide']],
            ['CANDOR', 'frankness', ['secrecy', 'greed', 'pride']],
        ];

        return $this->wordChoiceSet($sets, 'Which word is closest in meaning to "%s"?', 2);
    }

    private function verbalAntonyms(): array
    {
        $sets = [
            ['ABUNDANT', 'scarce', ['plentiful', 'huge', 'common']],
            ['GENEROUS', 'stingy', ['kind', 'wealthy', 'helpful']],
            ['CAUTIOUS', 'reckless', ['careful', 'slow', 'quiet']],
            ['EXPAND', 'shrink', ['grow', 'stretch', 'build']],
            ['TRANQUIL', 'chaotic', ['calm', 'quiet', 'peaceful']],
            ['HUMBLE', 'arrogant', ['modest', 'shy', 'kind']],
            ['TRANSPARENT', 'opaque', ['clear', 'honest', 'visible']],
            ['RIGID', 'flexible', ['stiff', 'strong', 'firm']],
            ['ANCIENT', 'modern', ['old', 'historic', 'classic']],
            ['OPTIMISTIC', 'pessimistic', ['hopeful', 'cheerful', 'bright']],
            ['GENUINE', 'fake', ['real', 'true', 'honest']],
            ['VOLUNTARY', 'mandatory', ['optional', 'free', 'willing']],
            ['CONCEAL', 'reveal', ['hide', 'bury', 'mask']],
            ['DILIGENT', 'lazy', ['hardworking', 'busy', 'skilled']],
            ['EXPAND', 'contract', ['grow', 'enlarge', 'rise']],
            ['FRUGAL', 'extravagant', ['thrifty', 'modest', 'poor']],
            ['GRADUAL', 'sudden', ['slow', 'steady', 'smooth']],
            ['HOSTILE', 'friendly', ['aggressive', 'angry', 'cold']],
            ['INNOCENT', 'guilty', ['pure', 'naive', 'kind']],
            ['JUBILANT', 'somber', ['joyful', 'excited', 'proud']],
            ['LETHARGIC', 'energetic', ['tired', 'slow', 'weak']],
            ['MAGNIFY', 'shrink', ['enlarge', 'boost', 'expand']],
            ['NOTORIOUS', 'reputable', ['infamous', 'known', 'famous']],
            ['OBEDIENT', 'defiant', ['loyal', 'compliant', 'quiet']],
            ['PERMANENT', 'temporary', ['lasting', 'fixed', 'stable']],
            ['QUAINT', 'ordinary', ['charming', 'old', 'unusual']],
            ['RELUCTANT', 'eager', ['unwilling', 'hesitant', 'shy']],
            ['SCARCE', 'abundant', ['rare', 'limited', 'short']],
            ['TIMID', 'bold', ['shy', 'quiet', 'nervous']],
            ['UNIFORM', 'varied', ['consistent', 'equal', 'plain']],
            ['VAGUE', 'precise', ['unclear', 'fuzzy', 'general']],
            ['WITHER', 'flourish', ['shrivel', 'fade', 'dry']],
            ['YIELD', 'resist', ['surrender', 'bend', 'submit']],
            ['ZEALOUS', 'apathetic', ['eager', 'devoted', 'active']],
        ];

        return $this->wordChoiceSet($sets, 'Which word means the opposite of "%s"?', 2);
    }

    private function verbalAnalogies(): array
    {
        $sets = [
            ['BIRD', 'NEST', 'BEE', 'hive', ['flower', 'wing', 'garden']],
            ['DOCTOR', 'HOSPITAL', 'TEACHER', 'school', ['book', 'student', 'lesson']],
            ['PEN', 'WRITE', 'KNIFE', 'cut', ['sharp', 'kitchen', 'metal']],
            ['FISH', 'WATER', 'BIRD', 'air', ['nest', 'feather', 'sky']],
            ['PUPPY', 'DOG', 'KITTEN', 'cat', ['pet', 'claw', 'milk']],
            ['AUTHOR', 'BOOK', 'COMPOSER', 'symphony', ['piano', 'note', 'concert']],
            ['THERMOMETER', 'TEMPERATURE', 'CLOCK', 'time', ['hour', 'watch', 'calendar']],
            ['CAR', 'GARAGE', 'PLANE', 'hangar', ['runway', 'pilot', 'sky']],
            ['WORD', 'SENTENCE', 'BRICK', 'wall', ['cement', 'house', 'door']],
            ['FINGER', 'HAND', 'PETAL', 'flower', ['stem', 'root', 'leaf']],
            ['LIBRARY', 'BOOKS', 'ORCHARD', 'trees', ['fruit', 'farmer', 'leaves']],
            ['KEY', 'LOCK', 'PASSWORD', 'account', ['computer', 'security', 'code']],
            ['CHEF', 'KITCHEN', 'MECHANIC', 'garage', ['tools', 'car', 'oil']],
            ['SCULPTOR', 'STATUE', 'CARPENTER', 'furniture', ['wood', 'hammer', 'nail']],
            ['SEED', 'TREE', 'EGG', 'bird', ['nest', 'feather', 'sky']],
            ['OCEAN', 'WAVE', 'DESERT', 'dune', ['sand', 'cactus', 'heat']],
            ['GLOVE', 'HAND', 'SHOE', 'foot', ['sock', 'lace', 'ankle']],
            ['STUDENT', 'CLASSROOM', 'PATIENT', 'hospital', ['nurse', 'doctor', 'medicine']],
            ['PAINTER', 'CANVAS', 'WRITER', 'paper', ['pen', 'ink', 'book']],
            ['ENGINE', 'CAR', 'HEART', 'body', ['blood', 'lung', 'brain']],
            ['CATERPILLAR', 'BUTTERFLY', 'TADPOLE', 'frog', ['pond', 'egg', 'water']],
            ['ISLAND', 'WATER', 'OASIS', 'desert', ['sand', 'palm', 'heat']],
            ['CONDUCTOR', 'ORCHESTRA', 'COACH', 'team', ['field', 'player', 'game']],
            ['ROOT', 'PLANT', 'FOUNDATION', 'building', ['wall', 'roof', 'brick']],
            ['MAP', 'TERRITORY', 'MENU', 'meal', ['restaurant', 'waiter', 'plate']],
        ];

        $questions = [];

        foreach ($sets as $i => [$a, $b, $c, $correct, $wrongs]) {
            $options = [$correct, ...$wrongs];

            $questions[] = $this->makeMcq(
                'verbal',
                "{$a} is to {$b} as {$c} is to ?",
                $options,
                0,
                2,
            );
        }

        return $questions;
    }

    private function verbalDeductions(): array
    {
        $contentSets = [
            ['tigers', 'mammals', 'animals'],
            ['roses', 'flowers', 'plants'],
            ['violins', 'string instruments', 'instruments'],
            ['maples', 'trees', 'plants'],
            ['sedans', 'cars', 'vehicles'],
            ['sparrows', 'birds', 'animals'],
        ];

        // Each form is a pair of premises plus whether the conclusion
        // "All X are Z" is Definitely True / Definitely False / Cannot Be Determined.
        $forms = [
            // All A are B. All B are C. -> All A are C. (valid syllogism, True)
            ['premise' => 'All %1$s are %2$s. All %2$s are %3$s.', 'answer' => 0],
            // All A are B. Some B are C. -> All A are C. (not guaranteed, Cannot Determine)
            ['premise' => 'All %1$s are %2$s. Some %2$s are %3$s.', 'answer' => 2],
            // All A are B. No B are C. -> All A are C. (contradiction, False)
            ['premise' => 'All %1$s are %2$s. No %2$s are %3$s.', 'answer' => 1],
        ];

        $options = ['Definitely true', 'Definitely false', 'Cannot be determined'];

        $questions = [];

        foreach ($contentSets as $set) {
            foreach ($forms as $form) {
                [$a, $b, $c] = $set;
                $premise = sprintf($form['premise'], ucfirst($a), $b, $c);

                $questions[] = $this->makeMcq(
                    'verbal',
                    "{$premise} Based only on these statements, is the following true, false, or undetermined: \"All {$a} are {$c}.\"",
                    $options,
                    $form['answer'],
                    3,
                );
            }
        }

        return $questions;
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: array<string>}>  $sets
     */
    private function wordChoiceSet(array $sets, string $promptTemplate, int $difficulty): array
    {
        $questions = [];

        foreach ($sets as [$word, $correct, $wrongs]) {
            $questions[] = $this->makeMcq(
                'verbal',
                sprintf($promptTemplate, $word),
                [$correct, ...$wrongs],
                0,
                $difficulty,
            );
        }

        return $questions;
    }

    // ---------------------------------------------------------------
    // Abstract reasoning
    // ---------------------------------------------------------------

    private function abstractLetterSequences(): array
    {
        $questions = [];

        // Skip-N letter sequences, e.g. A, C, E, G, ? (skip 1).
        for ($i = 0; $i < 12; $i++) {
            $startOrd = ord('A') + ($i % 10);
            $skip = 1 + ($i % 3);
            $letters = [];

            for ($k = 0; $k < 4; $k++) {
                $letters[] = chr($startOrd + $k * ($skip + 1));
            }

            $nextOrd = $startOrd + 4 * ($skip + 1);

            if ($nextOrd > ord('Z')) {
                continue;
            }

            $next = chr($nextOrd);

            $questions[] = $this->makeMcq(
                'abstract',
                'What letter comes next in the sequence: '.implode(', ', $letters).', ?',
                [$next, ...array_map(chr(...), $this->distinctOrdinals($nextOrd))],
                0,
                3,
                "Each letter skips {$skip} letter(s) in the alphabet from the one before it.",
            );
        }

        // Reverse-direction sequences, e.g. Z, X, V, T, ?
        for ($i = 0; $i < 6; $i++) {
            $startOrd = ord('Z') - ($i % 6);
            $skip = 2 + ($i % 2);
            $letters = [];

            for ($k = 0; $k < 4; $k++) {
                $letters[] = chr($startOrd - $k * ($skip + 1));
            }

            $nextOrd = $startOrd - 4 * ($skip + 1);

            if ($nextOrd < ord('A')) {
                continue;
            }

            $next = chr($nextOrd);

            $questions[] = $this->makeMcq(
                'abstract',
                'What letter comes next in the sequence: '.implode(', ', $letters).', ?',
                [$next, ...array_map(chr(...), $this->distinctOrdinals($nextOrd))],
                0,
                3,
                "Each letter moves backward {$skip} letter(s) in the alphabet from the one before it.",
            );
        }

        return $questions;
    }

    /**
     * Distinct letter ordinals (clamped to A-Z) different from $correct,
     * for use as wrong multiple-choice options.
     */
    private function distinctOrdinals(int $correct, int $count = 3): array
    {
        $candidates = range(ord('A'), ord('Z'));
        $candidates = array_filter($candidates, fn ($ord) => $ord !== $correct);
        usort($candidates, fn ($a, $b) => abs($a - $correct) <=> abs($b - $correct));

        return array_slice($candidates, 0, $count);
    }

    private function abstractOddOneOut(): array
    {
        $questions = [];

        // Multiples of N, one non-multiple.
        for ($i = 2; $i <= 9; $i++) {
            $n = $i;
            $group = [$n * 2, $n * 3, $n * 4, $n * 5];
            $odd = $n * 3 + 1;
            $numbers = [...$group, $odd];
            $numbers = $this->deterministicShuffle($numbers, $i);

            $questions[] = $this->makeMcq(
                'abstract',
                'Which number does not belong in this group: '.implode(', ', $numbers).'?',
                $numbers,
                array_search($odd, $numbers, true),
                3,
                "Every other number is a multiple of {$n}; {$odd} is not.",
            );
        }

        // Perfect squares, one non-square.
        for ($i = 2; $i <= 6; $i++) {
            $squares = [$i ** 2, ($i + 1) ** 2, ($i + 2) ** 2, ($i + 3) ** 2];
            $odd = ($i + 2) ** 2 + 2;
            $numbers = [...$squares, $odd];
            $numbers = $this->deterministicShuffle($numbers, $i + 10);

            $questions[] = $this->makeMcq(
                'abstract',
                'Which number does not belong in this group: '.implode(', ', $numbers).'?',
                $numbers,
                array_search($odd, $numbers, true),
                4,
                'Every other number is a perfect square; that one is not.',
            );
        }

        // Even vs. odd.
        for ($i = 1; $i <= 6; $i++) {
            $base = 10 * $i;
            $evens = [$base + 2, $base + 4, $base + 6, $base + 8];
            $odd = $base + 5;
            $numbers = [...$evens, $odd];
            $numbers = $this->deterministicShuffle($numbers, $i + 20);

            $questions[] = $this->makeMcq(
                'abstract',
                'Which number does not belong in this group: '.implode(', ', $numbers).'?',
                $numbers,
                array_search($odd, $numbers, true),
                1,
                'Every other number is even; that one is odd.',
            );
        }

        return $questions;
    }

    private function abstractShapePatterns(): array
    {
        $shapeSets = [
            ['■', '●'], // square, circle
            ['▲', '◆'], // triangle, diamond
            ['★', '◇'], // star, hollow diamond
        ];

        // Each period is shown for two full cycles, so the repeat is
        // unambiguous before asking for the first symbol of the third cycle.
        $periods = [
            'AB' => [0, 1],
            'AAB' => [0, 0, 1],
            'ABB' => [0, 1, 1],
            'AAAB' => [0, 0, 0, 1],
        ];

        $questions = [];

        foreach ($shapeSets as [$a, $b]) {
            foreach ($periods as $name => $period) {
                $symbolFor = fn ($idx) => $idx === 0 ? $a : $b;
                $cycle = array_map($symbolFor, $period);
                $sequence = [...$cycle, ...$cycle];
                $next = $symbolFor($period[0]);
                $wrong = $next === $a ? $b : $a;

                $questions[] = $this->makeMcq(
                    'abstract',
                    'What comes next in the pattern: '.implode(' ', $sequence).' ?',
                    [$next, $wrong],
                    0,
                    2,
                    'The pattern repeats every '.count($period)." symbols ({$name}), so after two full cycles it starts over.",
                );
            }
        }

        // Growing repetition count, e.g. ●, ●●, ●●●, ?
        for ($i = 1; $i <= 6; $i++) {
            $symbol = $i % 2 === 0 ? '■' : '●';
            $sequence = [
                str_repeat($symbol, 1),
                str_repeat($symbol, 2),
                str_repeat($symbol, 3),
            ];
            $next = str_repeat($symbol, 4);
            $wrong1 = str_repeat($symbol, 5);
            $wrong2 = str_repeat($symbol, 3);

            $questions[] = $this->makeMcq(
                'abstract',
                'What comes next in the pattern: '.implode(', ', $sequence).', ?',
                [$next, $wrong1, $wrong2],
                0,
                2,
                'The number of symbols increases by one each step.',
            );
        }

        return $questions;
    }

    /**
     * Wrong-but-plausible numeric options guaranteed distinct from the
     * correct answer and from each other (distinct offsets, same base).
     */
    private function distinctDistractors(int $correct, int $count = 3): array
    {
        $deltas = [1, -1, 2, -2, 3, -3, 4, -4, 5, -5];
        $result = [];

        foreach ($deltas as $delta) {
            $value = $correct + $delta;

            if ($value < 0) {
                continue;
            }

            $result[] = $value;

            if (count($result) === $count) {
                break;
            }
        }

        return $result;
    }

    /**
     * Deterministic (seed-based) shuffle so runs are reproducible.
     */
    private function deterministicShuffle(array $items, int $seed): array
    {
        mt_srand($seed);
        usort($items, fn () => mt_rand(-1, 1));
        mt_srand();

        return $items;
    }

    private function makeMcq(
        string $category,
        string $prompt,
        array $options,
        int $correctOption,
        int $difficulty,
        ?string $explanation = null,
    ): array {
        return [
            'category' => $category,
            'prompt' => $prompt,
            'options' => array_values(array_map(strval(...), $options)),
            'correct_option' => $correctOption,
            'difficulty' => $difficulty,
            'explanation' => $explanation,
        ];
    }
}
