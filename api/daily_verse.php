<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

function daily_verse_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET, OPTIONS');
    daily_verse_response(['error' => 'Method not allowed.'], 405);
}

$devotions = [
    [
        'reference' => 'Psalm 23',
        'text' => "The LORD is my shepherd; I shall not want.\n"
            . "He maketh me to lie down in green pastures: he leadeth me beside the still waters.\n"
            . "He restoreth my soul: he leadeth me in the paths of righteousness for his name's sake.\n"
            . "Yea, though I walk through the valley of the shadow of death, I will fear no evil: for thou art with me; thy rod and thy staff they comfort me.\n"
            . "Thou preparest a table before me in the presence of mine enemies: thou anointest my head with oil; my cup runneth over.\n"
            . "Surely goodness and mercy shall follow me all the days of my life: and I will dwell in the house of the LORD for ever.",
        'explanation' => 'Psalm 23 pictures God as a shepherd who guides, restores, and stays near through both peaceful and difficult seasons. A demanding class, an uncertain result, or a full schedule does not have to be faced all at once. Take the next faithful step, make space to rest, and remember that needing guidance is not failure.',
    ],
    [
        'reference' => 'Psalm 91',
        'text' => "He that dwelleth in the secret place of the most High shall abide under the shadow of the Almighty.\n"
            . "I will say of the LORD, He is my refuge and my fortress: my God; in him will I trust.\n"
            . "Surely he shall deliver thee from the snare of the fowler, and from the noisome pestilence.\n"
            . "He shall cover thee with his feathers, and under his wings shalt thou trust: his truth shall be thy shield and buckler.\n"
            . "Thou shalt not be afraid for the terror by night; nor for the arrow that flieth by day;\n"
            . "Nor for the pestilence that walketh in darkness; nor for the destruction that wasteth at noonday.\n"
            . "A thousand shall fall at thy side, and ten thousand at thy right hand; but it shall not come nigh thee.\n"
            . "Only with thine eyes shalt thou behold and see the reward of the wicked.\n"
            . "Because thou hast made the LORD, which is my refuge, even the most High, thy habitation;\n"
            . "There shall no evil befall thee, neither shall any plague come nigh thy dwelling.\n"
            . "For he shall give his angels charge over thee, to keep thee in all thy ways.\n"
            . "They shall bear thee up in their hands, lest thou dash thy foot against a stone.\n"
            . "Thou shalt tread upon the lion and adder: the young lion and the dragon shalt thou trample under feet.\n"
            . "Because he hath set his love upon me, therefore will I deliver him: I will set him on high, because he hath known my name.\n"
            . "He shall call upon me, and I will answer him: I will be with him in trouble; I will deliver him, and honour him.\n"
            . "With long life will I satisfy him, and shew him my salvation.",
        'explanation' => 'Psalm 91 uses the image of shelter to express trust in God amid uncertainty. It is not a promise that life will have no hard moments; it is a reminder that fear need not be your only companion in them. When exams, responsibilities, or change feel overwhelming, pause, seek support, and focus on the next thing you can do.',
    ],
    [
        'reference' => 'Romans 8:31-39',
        'text' => "What shall we then say to these things? If God be for us, who can be against us?\n"
            . "He that spared not his own Son, but delivered him up for us all, how shall he not with him also freely give us all things?\n"
            . "Who shall lay any thing to the charge of God's elect? It is God that justifieth.\n"
            . "Who is he that condemneth? It is Christ that died, yea rather, that is risen again, who is even at the right hand of God, who also maketh intercession for us.\n"
            . "Who shall separate us from the love of Christ? shall tribulation, or distress, or persecution, or famine, or nakedness, or peril, or sword?\n"
            . "As it is written, For thy sake we are killed all the day long; we are accounted as sheep for the slaughter.\n"
            . "Nay, in all these things we are more than conquerors through him that loved us.\n"
            . "For I am persuaded, that neither death, nor life, nor angels, nor principalities, nor powers, nor things present, nor things to come,\n"
            . "Nor height, nor depth, nor any other creature, shall be able to separate us from the love of God, which is in Christ Jesus our Lord.",
        'explanation' => 'Paul names real pressures—distress, hardship, and uncertainty—and anchors hope in a love that does not disappear when circumstances are difficult. A disappointing grade or a difficult week does not define your worth. Let this passage encourage you to ask for help, learn from setbacks, and continue without treating one result as the whole story.',
    ],
    [
        'reference' => 'Matthew 6:25-34',
        'text' => "Therefore I say unto you, Take no thought for your life, what ye shall eat, or what ye shall drink; nor yet for your body, what ye shall put on. Is not the life more than meat, and the body than raiment?\n"
            . "Behold the fowls of the air: for they sow not, neither do they reap, nor gather into barns; yet your heavenly Father feedeth them. Are ye not much better than they?\n"
            . "Which of you by taking thought can add one cubit unto his stature?\n"
            . "And why take ye thought for raiment? Consider the lilies of the field, how they grow; they toil not, neither do they spin:\n"
            . "And yet I say unto you, That even Solomon in all his glory was not arrayed like one of these.\n"
            . "Wherefore, if God so clothe the grass of the field, which to day is, and to morrow is cast into the oven, shall he not much more clothe you, O ye of little faith?\n"
            . "Therefore take no thought, saying, What shall we eat? or, What shall we drink? or, Wherewithal shall we be clothed?\n"
            . "(For after all these things do the Gentiles seek:) for your heavenly Father knoweth that ye have need of all these things.\n"
            . "But seek ye first the kingdom of God, and his righteousness; and all these things shall be added unto you.\n"
            . "Take therefore no thought for the morrow: for the morrow shall take thought for the things of itself. Sufficient unto the day is the evil thereof.",
        'explanation' => 'Jesus redirects attention from spiraling over every future possibility to faithfulness in the present day. This does not mean ignoring deadlines or refusing to plan; it means making a reasonable plan without letting worry consume the time needed to study, sleep, and care for yourself. Choose today’s priorities, then let tomorrow wait its turn.',
    ],
    [
        'reference' => 'Proverbs 3:1-12',
        'text' => "My son, forget not my law; but let thine heart keep my commandments:\n"
            . "For length of days, and long life, and peace, shall they add to thee.\n"
            . "Let not mercy and truth forsake thee: bind them about thy neck; write them upon the table of thine heart:\n"
            . "So shalt thou find favour and good understanding in the sight of God and man.\n"
            . "Trust in the LORD with all thine heart; and lean not unto thine own understanding.\n"
            . "In all thy ways acknowledge him, and he shall direct thy paths.\n"
            . "Be not wise in thine own eyes: fear the LORD, and depart from evil.\n"
            . "It shall be health to thy navel, and marrow to thy bones.\n"
            . "Honour the LORD with thy substance, and with the firstfruits of all thine increase:\n"
            . "So shall thy barns be filled with plenty, and thy presses shall burst out with new wine.\n"
            . "My son, despise not the chastening of the LORD; neither be weary of his correction:\n"
            . "For whom the LORD loveth he correcteth; even as a father the son in whom he delighteth.",
        'explanation' => 'These proverbs connect trust with humility, honesty, and teachability. Students make choices every day about how to use time, how to treat classmates, and how to respond when corrected. You do not have to know every outcome before beginning; seek wise counsel, act with integrity, and stay open to learning.',
    ],
    [
        'reference' => 'Philippians 4:4-9',
        'text' => "Rejoice in the Lord alway: and again I say, Rejoice.\n"
            . "Let your moderation be known unto all men. The Lord is at hand.\n"
            . "Be careful for nothing; but in every thing by prayer and supplication with thanksgiving let your requests be made known unto God.\n"
            . "And the peace of God, which passeth all understanding, shall keep your hearts and minds through Christ Jesus.\n"
            . "Finally, brethren, whatsoever things are true, whatsoever things are honest, whatsoever things are just, whatsoever things are pure, whatsoever things are lovely, whatsoever things are of good report; if there be any virtue, and if there be any praise, think on these things.\n"
            . "Those things, which ye have both learned, and received, and heard, and seen in me, do: and the God of peace shall be with you.",
        'explanation' => 'This passage offers a practical rhythm: name your concerns, give thanks for what is good, and deliberately return your thoughts to what is true and worthwhile. Before an exam or presentation, try writing down what is worrying you, one thing you appreciate, and one concrete action you can take next.',
    ],
    [
        'reference' => 'Isaiah 40:28-31',
        'text' => "Hast thou not known? hast thou not heard, that the everlasting God, the LORD, the Creator of the ends of the earth, fainteth not, neither is weary? there is no searching of his understanding.\n"
            . "He giveth power to the faint; and to them that have no might he increaseth strength.\n"
            . "Even the youths shall faint and be weary, and the young men shall utterly fall:\n"
            . "But they that wait upon the LORD shall renew their strength; they shall mount up with wings as eagles; they shall run, and not be weary; and they shall walk, and not faint.",
        'explanation' => 'Isaiah acknowledges that even young people become tired and discouraged. Needing rest does not mean you lack commitment. When your energy is low, pause, ask for support, and choose a sustainable next step rather than expecting yourself to do everything at once. Renewal is part of perseverance.',
    ],
    [
        'reference' => 'James 1:2-8',
        'text' => "My brethren, count it all joy when ye fall into divers temptations;\n"
            . "Knowing this, that the trying of your faith worketh patience.\n"
            . "But let patience have her perfect work, that ye may be perfect and entire, wanting nothing.\n"
            . "If any of you lack wisdom, let him ask of God, that giveth to all men liberally, and upbraideth not; and it shall be given him.\n"
            . "But let him ask in faith, nothing wavering. For he that wavereth is like a wave of the sea driven with the wind and tossed.\n"
            . "For let not that man think that he shall receive any thing of the Lord.\n"
            . "A double minded man is unstable in all his ways.",
        'explanation' => 'James does not call difficulty enjoyable; he points to the endurance and wisdom that can grow through it. A confusing assignment or a mistake can show you what to practice next. Ask questions early, seek guidance without shame, and give yourself time to build understanding one attempt at a time.',
    ],
    [
        'reference' => 'Psalm 121',
        'text' => "I will lift up mine eyes unto the hills, from whence cometh my help.\n"
            . "My help cometh from the LORD, which made heaven and earth.\n"
            . "He will not suffer thy foot to be moved: he that keepeth thee will not slumber.\n"
            . "Behold, he that keepeth Israel shall neither slumber nor sleep.\n"
            . "The LORD is thy keeper: the LORD is thy shade upon thy right hand.\n"
            . "The sun shall not smite thee by day, nor the moon by night.\n"
            . "The LORD shall preserve thee from all evil: he shall preserve thy soul.\n"
            . "The LORD shall preserve thy going out and thy coming in from this time forth, and even for evermore.",
        'explanation' => 'Psalm 121 is a song for a journey, reminding the reader that help and care continue along the way. A semester is also a journey made of ordinary departures and returns: going to class, studying, asking for feedback, and coming home to rest. You can take that journey one day at a time, and you do not have to carry every challenge alone.',
    ],
    [
        'reference' => '1 Corinthians 13',
        'text' => "Though I speak with the tongues of men and of angels, and have not charity, I am become as sounding brass, or a tinkling cymbal.\n"
            . "And though I have the gift of prophecy, and understand all mysteries, and all knowledge; and though I have all faith, so that I could remove mountains, and have not charity, I am nothing.\n"
            . "And though I bestow all my goods to feed the poor, and though I give my body to be burned, and have not charity, it profiteth me nothing.\n"
            . "Charity suffereth long, and is kind; charity envieth not; charity vaunteth not itself, is not puffed up,\n"
            . "Doth not behave itself unseemly, seeketh not her own, is not easily provoked, thinketh no evil;\n"
            . "Rejoiceth not in iniquity, but rejoiceth in the truth;\n"
            . "Beareth all things, believeth all things, hopeth all things, endureth all things.\n"
            . "Charity never faileth: but whether there be prophecies, they shall fail; whether there be tongues, they shall cease; whether there be knowledge, it shall vanish away.\n"
            . "For we know in part, and we prophesy in part.\n"
            . "But when that which is perfect is come, then that which is in part shall be done away.\n"
            . "When I was a child, I spake as a child, I understood as a child, I thought as a child: but when I became a man, I put away childish things.\n"
            . "For now we see through a glass, darkly; but then face to face: now I know in part; but then shall I know even as also I am known.\n"
            . "And now abideth faith, hope, charity, these three; but the greatest of these is charity.",
        'explanation' => 'This chapter describes love as patient, kind, humble, and enduring—not merely as a feeling or a display of talent. In student life, that can mean sharing credit, listening carefully in group work, responding respectfully under stress, and being patient with your own learning process. Achievement matters, but the way we treat people matters too.',
    ],
];

$day = (int) date('z');
$index = $day % count($devotions);

daily_verse_response($devotions[$index]);
