<?php

use App\Models\Article;
use App\Models\Tag;
use Illuminate\Support\Str;
use Carbon\Carbon;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$posts = [
    [
        'topic_id' => '019edf90-c519-7106-bbf4-aebb388c6e52', // Data Insights
        'content_type_id' => '019edf90-c020-715d-a070-8a5738f6dc5b', // Strategy Guide
        'title' => 'Mastering Two-Part Analysis (TPA): The Core Elimination Matrix for Data Insights',
        'slug' => 'mastering-two-part-analysis-tpa-core-elimination-matrix-data-insights',
        'summary' => 'Two-Part Analysis questions in the Data Insights section require simultaneous constraint evaluation. Master the matrix elimination method to solve TPA problems in under 2 minutes.',
        'content' => <<<'MD'
## Understanding Two-Part Analysis on the GMAT Focus
Two-Part Analysis (TPA) is arguably the most dynamic format on the GMAT Focus Data Insights section. Unlike standard multiple-choice questions where you select one option from five, TPA presents a table with two columns, and you must select one answer for Column 1 and one answer for Column 2.

To get credit for the question, **both selections must be correct**. There is no partial credit.

---

### The Three TPA Archetypes
1. **Mathematical TPA:** Essentially a word problem or systems of equations problem disguised in tabular format (e.g., finding the speed of Train A and Train B, or two values of $x$ and $y$).
2. **Verbal TPA:** Critical Reasoning or Reading Comprehension arguments where Column 1 asks for an assumption and Column 2 asks for a conclusion, or finding two mutually supportive statements.
3. **Integrated Logic TPA:** A hybrid scenario involving constraints, sequencing, or trade-offs (e.g., project scheduling, resource allocation).

---

### The 4-Step Elimination Matrix Protocol

```
1. Isolate Column Constraints
2. Evaluate Independent vs Dependent Choices
3. Apply Elimination to Column 1 First
4. Plug Column 1 Result into Column 2 Conditions
```

#### Step 1: Check If Answers Are Shared or Distinct
Look at the answer column labels. Can Column 1 and Column 2 choose the same row value, or must they select distinct rows? 
* If the prompt states *"Select one value for X and one value for Y"*, check whether $X = Y$ is mathematically possible or explicitly prohibited.

#### Step 2: Solve the More Constrained Column First
In nearly 80% of TPA questions, one column has significantly tighter constraints than the other.
* *Example:* If Column 1 must be a prime number between 20 and 40 (only 23, 29, 31, 37), whereas Column 2 can be any integer multiple of 3, **solve Column 1 first**. Narrow your candidate universe before calculating the second variable.

#### Step 3: Beware the "Order Sensitivity" Trap
Test-makers frequently reverse the column headers in the question prompt versus the table layout. Always double-check before submitting:
* Did you put the rate in Column 1 and the time in Column 2, or vice versa?

---

### Pro Timing Benchmark
* **Target Pacing:** 2 minutes to 2 minutes 15 seconds.
* If you have not isolated candidate answers by the 2:30 mark, select your best matrix candidates and move on to safeguard your pacing for subsequent Multi-Source Reasoning tabs.
MD,
        'reading_time_minutes' => 6,
        'views_count' => 380,
    ],
    [
        'topic_id' => '019edf90-c7a6-703f-998a-686fcbd0f8df', // Reading Comprehension
        'content_type_id' => '019edf90-c0aa-70bc-b3cf-9b276c5a89e6', // Cheat Sheet
        'title' => 'The 3-Pass Method for GMAT Focus Reading Comprehension Passages',
        'slug' => '3-pass-method-gmat-focus-reading-comprehension',
        'summary' => 'Stop re-reading entire passages for every question. Master the 3-pass active reading protocol to retain structural nuance without drowning in technical jargon.',
        'content' => <<<'MD'
## The Trap of Passive Reading
Many test-takers read GMAT Reading Comprehension (RC) passages like a textbook or a novel, absorbing every minute chemical formula, historical date, or economic indicator. 
By the time they reach Question 1, they have burned 4 minutes and retained zero understanding of the author's argument.

On the GMAT Focus, RC is not a test of photographic memory—it is a test of **discourse structure and author intent**.

---

### The 3-Pass Active Reading Framework

```
Pass 1: Macro-Architecture (2.5 mins)
Pass 2: Pivot & Transition Anchoring (During Pass 1)
Pass 3: Surgical Verification (30-45s per question)
```

#### Pass 1: Extract the Structural Skeleton
As you read each paragraph, force yourself to write a 5-to-7 word mental or scratchpad summary:
* *Paragraph 1:* "Old theory on plate tectonics & flaws."
* *Paragraph 2:* "New seismic data proposed by Dr. Vance."
* *Paragraph 3:* "Author's reservations regarding data sample size."

Notice what is missing: specific rock names, Richter scale figures, and laboratory locations. **Omit the details, keep the skeleton.**

#### Pass 2: Track "Pivot Signals"
Author opinion hinges on contrast markers. Whenever you encounter these words, pay extreme attention:
* *Traditional View:* "Traditionally assumed...", "Most historians argue...", "It was once thought..."
* *The Pivot:* "However...", "Nonetheless...", "Recent evidence contradicts..."
* *The Synthesis:* "Consequently...", "This suggests that..."

The correct answers to "Primary Purpose" and "Author's Attitude" questions are almost always found within 10 words of these pivot signals.

#### Pass 3: Surgical Target Verification
When answering specific detail or inference questions:
1. Identify the **key noun phrase** in the question stem.
2. Return directly to the specific paragraph identified in your Pass 1 skeleton.
3. Read 2 lines above and 2 lines below the anchor phrase.
4. Eliminate answer choices that use exact passage wording to distort the causal relationship.
MD,
        'reading_time_minutes' => 5,
        'views_count' => 420,
    ],
    [
        'topic_id' => '019edf90-c6c8-7153-8751-0741313e60f4', // Data Sufficiency
        'content_type_id' => '019edf90-c020-715d-a070-8a5738f6dc5b', // Strategy Guide
        'title' => 'Data Sufficiency in Quant: Why You Must Never Calculate the Exact Number',
        'slug' => 'data-sufficiency-quant-never-calculate-exact-number',
        'summary' => 'The biggest time-sink in GMAT Data Sufficiency is calculating values that do not matter. Discover the Sufficiency Boundary technique to solve DS problems in 60 seconds.',
        'content' => <<<'MD'
## The Fundamental Distinction of Data Sufficiency
Data Sufficiency (DS) does not ask: *"What is the value of x?"*
It asks: *"Do you have enough information to determine whether x has a single unique value, or whether a definitive Yes/No conclusion can be drawn?"*

Every second you spend carrying out long division, expanding high-degree polynomials, or finding precise decimal coordinates is wasted cognitive energy.

---

### The Two Types of DS Questions

| Question Type | Criterion for Sufficiency | Fatal Error |
| :--- | :--- | :--- |
| **Value Question** | Exactly ONE unique value exists | Accepting two candidate values (e.g. $x = 3$ or $x = -3$) |
| **Yes/No Question** | A definitive YES or a definitive NO | Thinking "NO" means insufficient! A definite NO is 100% SUFFICIENT |

---

### The "Sufficiency Boundary" Technique
Instead of calculating the solution, evaluate the degrees of freedom:
1. **Count Equations vs Variables:** In linear systems without geometric or integer constraints, $N$ independent equations determine $N$ variables. Stop the moment you confirm the equations are linear and non-redundant.
2. **Beware Integer Constraints:** If the prompt specifies that $p$ and $q$ are *positive integers*, standard linear rules no longer apply. A single equation like $5p + 7q = 41$ may yield only one valid positive integer pair ($p=4, q=3$), making a single equation sufficient!
3. **The Carryover Hazard:** When evaluating Statement (2), completely erase Statement (1) from your working memory. Contaminating Statement (2) with information from Statement (1) is the #1 reason candidates choose C instead of B.

---

### Key Takeaway Formula
$$\text{Data Sufficiency Mastery} = \text{Rephrase Question Stem} \to \text{Evaluate (1)} \to \text{Evaluate (2)} \to \text{Combine Only If Neither is Alone Sufficient}$$
MD,
        'reading_time_minutes' => 5,
        'views_count' => 495,
    ],
    [
        'topic_id' => '019edf90-c885-71d0-87a9-87a026c0ff1e', // Boldface Role Analysis
        'content_type_id' => '019edf90-c020-715d-a070-8a5738f6dc5b', // Strategy Guide
        'title' => 'Cracking Boldface Critical Reasoning: The 2-Tier Argument Mapping System',
        'slug' => 'cracking-boldface-critical-reasoning-2-tier-argument-mapping',
        'summary' => 'Boldface CR questions intimidate test-takers with dense structural jargon. Master the 2-Tier Argument Map to eliminate 3 wrong options in 30 seconds.',
        'content' => <<<'MD'
## Deciphering Boldface Questions Without Panic
Boldface Critical Reasoning questions look terrifying because of their verbose answer choices filled with phrases like *"the first is an objection raised against an intermediate conclusion that is later superseded..."*

However, Boldface questions are actually the most formulaic and mechanical questions on the entire Verbal section once you master the **2-Tier Argument Map**.

---

### Tier 1: The Direction Test (+ vs -)
Every boldface portion falls into one of two camps:
* **The Author's Camp (+):** Supports the author's primary conclusion or represents the author's own claim.
* **The Opponent's Camp (-):** Opposes the author's conclusion, represents a counterargument, an alternative theory, or an objection.

Before reading the choices, map the two bolded portions:
* Is it $(+, +)$, $(+, -)$, $(-, +)$, or $(-, -)$?

**Test Result:** This simple polarity check eliminates 2 to 3 answer choices immediately without parsing their convoluted syntax.

---

### Tier 2: The Role Classification (Fact vs Claim)
For the remaining choices, classify the nature of each boldface fragment:
1. **Objective Evidence (Premise / Fact):** Historical data, survey results, experimental observations, or accepted physical constants.
2. **Intermediate Conclusion:** A claim supported by prior evidence that simultaneously serves as evidence for the overarching conclusion.
3. **Main Conclusion:** The ultimate claim the author is attempting to prove, with no subsequent claims depending upon it.
4. **Counter-Evidence:** A fact acknowledged by the author that temporarily appears to challenge their position.

---

### Translation Guide for Common GMAT Answer Phrases
* *"A consideration offered in support of..."* $\rightarrow$ A premise or evidence.
* *"The position that the argument seeks to establish..."* $\rightarrow$ The main conclusion.
* *"An explanation that the author challenges..."* $\rightarrow$ An opponent's claim or hypothesis.
* *"A judgment that provides context for the subsequent argument..."* $\rightarrow$ Background information.
MD,
        'reading_time_minutes' => 6,
        'views_count' => 460,
    ],
    [
        'topic_id' => '019edf90-c519-7106-bbf4-aebb388c6e52', // Data Insights
        'content_type_id' => '019edf90-c1b7-7287-907f-4af2b45a0b40', // Daily Concept
        'title' => 'Graphics Interpretation (GI) Pacing: Reading Dropdowns Like an MBA Consultant',
        'slug' => 'graphics-interpretation-gi-pacing-dropdowns-mba-consultant',
        'summary' => 'Graphics Interpretation tests your ability to spot axis scales, deceptive baselines, and trend reversals under extreme time pressure. Here is how to ace them.',
        'content' => <<<'MD'
## The Cognitive Illusion of Visual Data
Visual displays of data are designed to summarize trends quickly, but in the GMAT Focus Data Insights section, chart graphics are specifically engineered to exploit visual heuristics.

A casual glance often leads to incorrect conclusions because axes are inverted, baselines are non-zero, or dual axes use wildly different unit scales.

---

### The 4 Visual Traps in GMAT Focus Charts

#### 1. The Non-Zero Baseline (Truncated Axis)
When a bar graph starts at 90 instead of 0, a difference between 92 and 96 appears quadrupled visually, even though the actual proportional increase is merely $\approx 4.3\%$. Always check tick-mark labels before assuming proportional magnitude.

#### 2. Dual Y-Axis Mismatch
When a graph displays two variables (e.g., *Revenue* in Millions on the left Y-axis and *Profit Margin %* on the right Y-axis), verify which curve corresponds to which axis legend. Test-makers frequently craft wrong answers that mix the left-axis scale with the right-axis curve.

#### 3. Bivariate Scatterplot Correlations vs Outliers
Scatterplots often test median values or trend lines. Remember:
* If a question asks for the **median**, do not calculate the average. Count the dots from left-to-right (for the X-median) or bottom-to-top (for the Y-median).

#### 4. Reading Dropdown Options BEFORE Calculating
Before you perform arithmetic:
1. Click open the dropdown menu to inspect the range of options.
2. If the options are widely spaced (e.g., $10\%$, $25\%$, $50\%$, $80\%$), **estimate aggressively**. You do not need exact division.
3. If options are tightly grouped (e.g., $24.2\%$, $24.8\%$, $25.4\%$), precise calculation is required.
MD,
        'reading_time_minutes' => 5,
        'views_count' => 350,
    ],
    [
        'topic_id' => '019edf90-c98f-739a-b4c7-bfd7d57c1c29', // MBA Admissions Strategy
        'content_type_id' => '019edf90-c142-73e0-afac-ca4a83eb3e69', // Score Journey
        'title' => 'From 605 to 735: The Complete 10-Week GMAT Focus Study Architecture',
        'slug' => 'from-605-to-735-complete-10-week-gmat-focus-study-architecture',
        'summary' => 'A transparent, day-by-day retrospective of jumping from the 68th to the 99th percentile under the new GMAT Focus scoring scale, including weekly error logs and mock cadence.',
        'content' => <<<'MD'
## The Starting Point: Diagnostic Shock
When I took my first official GMAT Focus mock exam, I scored a **605 (Q79, V81, DI78)**. As someone aiming for Stanford GSB and Wharton, seeing my percentile in the high 60s was a wake-up call.

Ten weeks later, on my official test day, I walked out of the Pearson VUE center with a **735 (Q88, V85, DI84)**—placing in the 99th percentile globally.

Here is the exact blueprint I followed to engineer that 130-point leap.

---

### The 3 Study Phases

#### Phase 1: Conceptual Mastery & Error Root-Cause Logging (Weeks 1–4)
* **Rule #1:** No timed sets yet. Speed without accuracy is merely automated guessing.
* Built my **Error Log Taxonomy** with 4 non-negotiable columns:
  1. *Sub-topic tested* (e.g., Number Properties - Prime Factorization).
  2. *Why I chose the wrong answer* (Fell for out-of-scope distractor / Misread question stem).
  3. *The underlying conceptual flaw* (Did not know 1 is neither prime nor composite).
  4. *The Takeaway Rule* (One actionable sentence to review before every practice session).

#### Phase 2: Sectional Pacing & Stamina Adaptation (Weeks 5–7)
* Switched to 21-question timed blocks under strict 45-minute limits.
* Learned to make peace with **strategic guessing**: letting go of ultra-dense 750-level questions at the 2:30 mark preserved mental energy for the rest of the section.

#### Phase 3: Full-Length Official Mocks & Condition Rehearsal (Weeks 8–10)
* Took 6 Official Practice Exams (Focus 1 through 6) at the exact hour of my scheduled test (9:00 AM).
* Practiced testing with the physical erasable scratchpad and ultra-fine dry erase marker.
* Locked in my optimal section sequence: **Quant $\to$ 10-Min Break $\to$ Verbal $\to$ Data Insights**.

---

### The Golden Rule of 700+ GMAT Focus Prep
> "Reviewing 20 questions with meticulous depth is worth ten times more than blindly speed-running 100 questions."
MD,
        'reading_time_minutes' => 7,
        'views_count' => 540,
    ],
    [
        'topic_id' => '019edf90-c77a-71bb-baf1-e98318fb2556', // Critical Reasoning
        'content_type_id' => '019edf90-c22e-72c1-b545-ca79ee86fd7f', // Mistake Autopsy
        'title' => 'The Trap of Extremes: How to Spot and Avoid Fatal Flaws in CR Inferences',
        'slug' => 'trap-of-extremes-fatal-flaws-cr-inferences',
        'summary' => 'Why do high-scoring test-takers pick the wrong inference? Unpack the subtle psychological trap of extreme qualifiers versus necessary logical implications.',
        'content' => <<<'MD'
## The Anatomy of an Inference Trap
In GMAT Focus Critical Reasoning, an **Inference** question asks: *"Which of the following must be true based strictly on the statements above?"*

Unlike real-world business deductions where we make reasonable extrapolations, formal GMAT logic requires **100% mathematical certainty**. 
The test writers exploit our natural intuition by creating tempting answer choices that are *probable*, *plausible*, or *emotionally satisfying*, but not logically guaranteed.

---

### The Linguistic Spectrum of Certainty

```
EXTREME (Avoid in Inferences unless stated in premise)
Must | Always | Never | Completely | Impossible | Solely | Guarantees

MODERATE (High probability of being correct)
May | Often | Some | Tends to | Can | Not necessarily | At least one
```

### Case Study: An Official-Style Stimulus

> *Stimulus:* In the manufacturing of composite aircraft wings, robotic laser bonding reduces micro-fissures by 60% compared to traditional manual rivet techniques. However, facilities utilizing laser bonding report a 15% increase in annual calibration downtime.

#### The Traps:
* ❌ *Trap Choice A:* "Manual rivet techniques will soon be completely phased out in commercial aviation." *(Extreme extrapolation: the passage never discusses future phase-outs).*
* ❌ *Trap Choice B:* "Aircraft wings produced via manual rivets are unsafe for passenger travel." *(Value judgment and extreme distortion).*
* ❌ *Trap Choice C:* "Laser bonding is more expensive than manual riveting." *(Out-of-scope: downtime increased, but total maintenance and manufacturing costs are unknown).*
* ✅ *Correct Choice D:* "At least some composite aircraft wings produced with manual rivets develop micro-fissures." *(Guaranteed: laser bonding reduces them by 60%, which logically requires that micro-fissures exist in manual rivet production).*

---

### The Takeaway Check
When reviewing your inference errors, ask: *"Did the author explicitly force this conclusion, or did I bring in external common sense to bridge the gap?"* If you bridged the gap yourself, it is wrong.
MD,
        'reading_time_minutes' => 6,
        'views_count' => 410,
    ],
    [
        'topic_id' => '019edf90-c690-720a-8d3d-31ffe643184f', // Problem Solving
        'content_type_id' => '019edf90-c0aa-70bc-b3cf-9b276c5a89e6', // Cheat Sheet
        'title' => 'Work-Rate & Combined Efficiency: The LCM Unit Method Over Fractions',
        'slug' => 'work-rate-combined-efficiency-lcm-unit-method',
        'summary' => 'Stop dealing with messy fractions like 1/x + 1/y. Discover how setting total work to the Least Common Multiple (LCM) turns rate problems into simple integer math.',
        'content' => <<<'MD'
## Why Fraction Addition Slows You Down
In school, everyone learns the reciprocal rate formula for combined work:
$$\frac{1}{T} = \frac{1}{A} + \frac{1}{B} + \frac{1}{C}$$

While algebraically correct, adding fractions with denominators like 12, 18, and 24 under a 2-minute time limit invites arithmetic errors and wastes valuable scratchpad space.

The **LCM Unit Method** eliminates fractions entirely.

---

### The 3-Step LCM Method

#### Step 1: Assign a Convenient "Total Work" Value
Find the Least Common Multiple (LCM) of the given time values and assign it as the total number of "work units."

* *Example:* Pump A fills a tank in 12 hours. Pump B fills it in 18 hours. Pump C empties it in 24 hours.
* $\text{LCM}(12, 18, 24) = 72 \text{ units}$.

#### Step 2: Compute Integer Rates per Hour
* Pump A rate $= \frac{72}{12} = +6 \text{ units/hr}$
* Pump B rate $= \frac{72}{18} = +4 \text{ units/hr}$
* Pump C rate $= \frac{72}{24} = -3 \text{ units/hr}$ (Negative because it empties)

#### Step 3: Combine Rates with Clean Addition
$$\text{Net Combined Rate} = 6 + 4 - 3 = 7 \text{ units/hr}$$
$$\text{Time to fill tank} = \frac{\text{Total Units}}{\text{Net Rate}} = \frac{72}{7} = 10\frac{2}{7} \text{ hours}$$

---

### Handling "Alternating Work" and "Early Departure"
* If Worker A works alone for 3 hours before Worker B joins:
  * Calculate work done by A: $3 \times 6 = 18 \text{ units}$.
  * Calculate remaining work: $72 - 18 = 54 \text{ units}$.
  * Time for A and B combined: $\frac{54}{6 + 4} = \frac{54}{10} = 5.4 \text{ hours}$.
* No fractions, zero common denominator calculations, 100% speed.
MD,
        'reading_time_minutes' => 5,
        'views_count' => 475,
    ],
    [
        'topic_id' => '019edf90-c98f-739a-b4c7-bfd7d57c1c29', // MBA Admissions Strategy
        'content_type_id' => '019edf90-c020-715d-a070-8a5738f6dc5b', // Strategy Guide
        'title' => 'GMAT Focus Scoring & Top B-School Admissions: Mapping Percentiles for M7 & T15',
        'slug' => 'gmat-focus-scoring-top-bschool-admissions-mapping-m7-t15',
        'summary' => 'How do admissions committees at HBS, Stanford GSB, Wharton, and Booth evaluate GMAT Focus scores compared to the classic 800 scale? Here is the data-backed reality.',
        'content' => <<<'MD'
## Navigating the Focus Edition Shift in MBA Admissions
With GMAC's full transition to the GMAT Focus Edition, the historic benchmarks of "700" and "750" have shifted dramatically. 

Because the Focus score ranges from **205 to 805**, many applicants mistakenly believe that a score of 655 is "below average." In reality, a **655 on the Focus Edition corresponds to the 89th percentile**—virtually identical to a 710 on the legacy exam!

---

### Score Concordance Table: Focus vs Classic

| GMAT Focus Score | Global Percentile | Classic GMAT Equivalent | M7 / T15 Competitiveness |
| :--- | :--- | :--- | :--- |
| **735–805** | 99th–100th %ile | 780–800 | Top scholarship tier at all M7 programs |
| **695–715** | 98th–99th %ile | 750–770 | Above median at HBS, Stanford, Wharton |
| **665–685** | 92nd–97th %ile | 720–740 | Competitive baseline for all Top-15 schools |
| **635–655** | 82nd–89th %ile | 690–710 | Solid for T15 / T25; offset with strong GPA/WE |
| **595–625** | 64th–78th %ile | 640–680 | Target European/Regional programs or re-take |

---

### How AdComs Evaluate Section Balance
Under the GMAT Focus, all three sections (Quantitative Reasoning, Verbal Reasoning, and Data Insights) carry equal weight in the total score.
* **The Data Insights Section:** Because DI was formerly uncounted Integrated Reasoning, top B-schools now scrutinize DI percentiles to assess digital fluency and data synthesis aptitude.
* **Avoid Sectional Skew:** An applicant with Q88 / V85 / DI72 will face questions regarding data interpretation. Aim to keep all three section percentiles above the 70th percentile.

---

### When to Retake vs When to Focus on Essays
If your Focus score is within 10 points of your target school's published median (e.g., 665 for a school with a 675 median), **stop studying for the test**. 
Invest the remaining time in polishing your leadership narrative, securing compelling recommendations, and articulating why that specific business school is your ideal career accelerator.
MD,
        'reading_time_minutes' => 6,
        'views_count' => 520,
    ],
    [
        'topic_id' => '019edf90-c2d9-72ff-a91c-4bc016f4cf13', // Quantitative Reasoning
        'content_type_id' => '019edf90-c020-715d-a070-8a5738f6dc5b', // Strategy Guide
        'title' => 'The Optimal Section Order on GMAT Focus: Quant, Verbal, or Data Insights First?',
        'slug' => 'optimal-section-order-gmat-focus-quant-verbal-data-insights',
        'summary' => 'With 6 different section orders available and one 10-minute break, your section sequencing directly dictates cognitive fatigue. Find the optimal order for your test persona.',
        'content' => <<<'MD'
## The Power of Strategic Section Choice
One of the most valuable features of the GMAT Focus Edition is total flexibility in section sequencing. You can select any of the **6 permutations** of Quant (Q), Verbal (V), and Data Insights (DI).

Furthermore, you are granted **one optional 10-minute break**, which you can place after your first section or after your second section.

Choosing the wrong sequence can lead to mental burnout during the most demanding section of your exam.

---

### The Three Proven Section Order Personas

#### Order 1: The Analytical Warmup (Quant $\to$ Break $\to$ Verbal $\to$ Data Insights)
* **Best For:** Test-takers whose strongest area is Quant.
* **Why It Works:** Starting with Quant builds early confidence and gets the adrenaline flowing. Taking the break immediately after Quant clears your head before facing dense Verbal reading passages.
* **Watch Out:** Data Insights comes last; make sure you have practiced 2-hour full-length mocks so fatigue does not compromise table analysis.

#### Order 2: The Mental Stamina Preserver (Verbal $\to$ Break $\to$ Quant $\to$ Data Insights)
* **Best For:** Non-native English speakers or those who find Reading Comprehension mentally draining.
* **Why It Works:** Verbal requires high-intensity linguistic and logical discernment. Tackling it when your brain is 100% fresh prevents comprehension drift and second-guessing.

#### Order 3: The Data-First Approach (Data Insights $\to$ Break $\to$ Quant $\to$ Verbal)
* **Best For:** Candidates who find Data Insights to be their highest-risk section.
* **Why It Works:** DI contains the highest information density (multi-tab reasoning, visual graphs, multi-column tables). Clearing it first eliminates test anxiety.

---

### Break Protocol Checklist
* **Always take the 10-minute break.** Never skip it to "stay in the zone."
* Leave the testing station, drink water, consume a high-glycemic snack (e.g., banana, dates, or dark chocolate), and stretch your shoulders and neck.
* Return to your seat with 2 minutes to spare to allow for biometric check-in.
MD,
        'reading_time_minutes' => 6,
        'views_count' => 485,
    ],
];

$allTags = Tag::all();
$inserted = 0;
$now = Carbon::now();

foreach ($posts as $idx => $data) {
    $publishedAt = $now->copy()->subMinutes(10 - $idx);

    $article = Article::create([
        'topic_id' => $data['topic_id'],
        'content_type_id' => $data['content_type_id'],
        'title' => $data['title'],
        'slug' => $data['slug'],
        'summary' => $data['summary'],
        'content' => $data['content'],
        'status' => 'published',
        'published_at' => $publishedAt,
        'views_count' => $data['views_count'],
        'reading_time_minutes' => $data['reading_time_minutes'],
        'meta_title' => $data['title'] . ' | GmatGroup',
        'meta_description' => Str::limit($data['summary'], 155),
        'is_featured' => 1,
    ]);

    // Attach 2-3 random tags
    if ($allTags->count() >= 3) {
        $randomTagIds = $allTags->random(rand(2, 4))->pluck('id')->toArray();
        $article->tags()->sync($randomTagIds);
    }

    echo "Created: [{$article->id}] {$article->title}\n";
    $inserted++;
}

echo "Successfully created {$inserted} new articles!\n";
