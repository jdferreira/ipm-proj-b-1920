<?php

class Database
{
    /** @var SQLite3 $conn */
    private static $conn;

    static function init()
    {
        // Check whether the database exists, and create it if it doesn't
        // Create a static connection to the database
        if (file_exists('db/projb.db')) {
            static::$conn = new SQLite3('db/projb.db');
        } else {
            static::$conn = new SQLite3('db/projb.db');
            static::createTables();
        }
    }

    private static function createTables()
    {
        static::$conn->exec('
            CREATE TABLE generated_sequences (
                "id" INTEGER PRIMARY KEY AUTOINCREMENT,
                "group" TEXT,
                "time" INTEGER,
                "seed" INTEGER,
                "hash" TEXT,
                "width" INTEGER,
                "height" INTEGER,
                "length" INTEGER
            )
        ');

        static::$conn->exec('
            CREATE TABLE performance (
                "id" INTEGER PRIMARY KEY AUTOINCREMENT,
                "sequence_id" INTEGER,
                "age" INTEGER,
                "successes" INTEGER,
                "failures" INTEGER,
                "mistakes" INTEGER,
                "elapsed" NUMERIC,
                "user_id" TEXT
            )
        ');

        static::$conn->exec('
            CREATE TABLE interactions (
                "id" INTEGER PRIMARY KEY AUTOINCREMENT,
                "performance_id" INTEGER,
                "x" NUMERIC,
                "y" NUMERIC,
                "distance" NUMERIC,
                "elapsed" NUMERIC,
                "type" TEXT,
                "class" TEXT
            )
        ');
    }

    static function store($tablename, $data)
    {
        $columns = [];
        $qmarks = [];
        $values = [];

        foreach ($data as $column => $value) {
            $columns[] = '"' . $column . '"';
            $qmarks[] = ':' . $column;
            $values[] = $value;
        }

        $columns = implode(',', $columns);
        $qmarks = implode(',', $qmarks);

        $stmt = static::$conn->prepare(
            "INSERT INTO $tablename ($columns) VALUES ($qmarks)"
        );

        foreach ($data as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }

        $stmt->execute();

        return static::$conn->lastInsertRowID();
    }

    static function query($query, $data = [])
    {
        $stmt = static::$conn->prepare($query);

        foreach ($data as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }

        return $stmt->execute();
    }
}

Database::init();

class SequenceGenerator
{
    private static $salt = 'Kr8QpbQeI3cPbufm7TWuNAxWMd0pDCvl';

    private $width;
    private $height;
    private $length;

    public function __construct($width, $height, $length)
    {
        $this->width = $width;
        $this->height = $height;
        $this->length = $length;
    }

    function generate($seed)
    {
        $oldSeed = rand();

        srand($seed);

        $result = [];

        for ($i = 0; $i < $this->length; $i++) {
            $result[] = [
                rand(0, $this->width - 1),
                rand(0, $this->height - 1)
            ];
        }

        srand($oldSeed);

        return $result;
    }

    function store($seed)
    {
        // Compute hash, store the values (including the hash) and return the hash
        $group = Http::getGroup();

        if ($group === null) {
            $hash = null;
        } else {
            $hash = hash('sha256', static::$salt . $seed . $group);
            $time = time();

            Database::store('generated_sequences', [
                'group' => $group,
                'time' => $time,
                'seed' => $seed,
                'hash' => $hash,
                'width' => $this->width,
                'height' => $this->height,
                'length' => $this->length,
            ]);
        }

        return $hash;
    }
}

class Performance
{
    private $row;
    private $interactions;

    public function __construct($performance)
    {
        $keys = [
            'age',
            'successes',
            'mistakes',
            'failures',
            'elapsed',
            'user_id',
        ];

        foreach ($keys as $key) {
            if (!isset($performance[$key])) {
                throw new Exception("Missing parameter '$key'");
            }

            $this->row[$key] = $performance[$key];
        }

        if (!isset($performance['hash'])) {
            throw new Exception("Missing parameter hash'");
        }
        $this->row['sequence_id'] = $this->getSequenceIdFromHash($performance['hash']);

        if (!isset($performance['interactions'])) {
            throw new Exception("Missing parameter 'interactions'");
        }
        $this->interactions = $performance['interactions'];
    }

    private function getSequenceIdFromHash($hash)
    {
        $result = Database::query(
            'SELECT "id" FROM "generated_sequences" WHERE "hash" = :hash',
            ['hash' => $hash]
        );

        if ($row = $result->fetchArray()) {
            return $row['id'];
        } else {
            throw new Exception("Cannot find hash $hash");
        }
    }

    static function fromRequest()
    {
        $performance = Http::getJsonPostData();

        return new self($performance);
    }

    function store()
    {
        $id = Database::store('performance', $this->row);

        foreach ($this->interactions as $interaction) {
            $interaction['performance_id'] = $id;

            Database::store('interactions', $interaction);
        }
    }
}

class Http
{
    static function getGroup()
    {
        $correctHost = preg_match(
            '/^https?:\/\/areasgrupo\.alunos\.di\.fc\.ul\.pt/',
            $_SERVER['HTTP_REFERER']
        );

        if (!$correctHost) {
            return null;
        }

        preg_match('/~ipm(\d{3})/', $_SERVER['HTTP_REFERER'], $matches);

        return (int) $matches[1];
    }

    static function getJsonPostData()
    {
        return json_decode(file_get_contents('php://input'), true);
    }

    static function errorResponse($errors)
    {
        static::response([
            'success' => false,
            'errors' => (array) $errors
        ]);
    }

    static function successResponse($data)
    {
        static::response([
            'success' => true,
            'data' => $data
        ]);
    }

    private static function response($body)
    {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

        echo json_encode($body);

        exit;
    }
}

class GroupCodes
{
    private static $codeMap = [
        'JN9LC-A4BGF-HD64P' => 0,
        'KE9N3-6FRMX-GXEQR' => 1,
        'AQK6K-BHPAY-KBAT4' => 2,
        'E93P8-6C3RW-KN83G' => 3,
        'KW3TY-N6XGN-FCG4T' => 4,
        'LWYXT-3W8YX-KDR63' => 5,
        'X3KPK-K6JNN-QLFFC' => 6,
        'PP8WP-CLB9H-WC996' => 7,
        'LRCPG-6XCGY-H8KE3' => 8,
        'C3GHW-WXJ3W-LDHMY' => 9,
        'QGP4Y-P3WJR-8PDK8' => 10,
        'DKK3G-J3QKM-Q6W9T' => 11,
        'KHMBA-NXNDL-M4JAJ' => 12,
        'HYECX-H9HBQ-MQXDR' => 13,
        'KMKWY-8TXRC-GE3D8' => 14,
        'QNYTP-BLMMT-ECQDA' => 15,
        'KCGP3-AQANX-GKXQK' => 16,
        'T6LWT-JRA4K-XY8L8' => 17,
        'DGAMQ-XLN9R-3C3T8' => 18,
        'MYT6T-9RGWW-CJQX4' => 19,
        'YQ9QT-W466J-RRBMC' => 20,
        'GL8AB-GWQQR-4HR6X' => 21,
        'AFEH8-4C34D-CY8AH' => 22,
        '8ND4T-6RLHA-MH4QQ' => 23,
        'NDR98-XGDX9-XXQRG' => 24,
        '4JGTK-PR6BG-CWWCL' => 25,
        'GCJKX-6CM8R-NXHFB' => 26,
        'JB8Y3-W6T4J-YEH3Q' => 27,
        'CMXFG-DFR63-6XERG' => 28,
        'GLJJC-C6TFA-RXQY9' => 29,
        'XLRA9-F3LGB-4HWCT' => 30,
        '9G4KQ-BCQNB-96EFT' => 31,
        '3XY8H-CFLT9-MFYBR' => 32,
        'HQ6M3-P69NQ-J4XRN' => 33,
        'QWXTC-4WRWM-PAH4N' => 34,
        'PTHFM-9383K-FQCFF' => 35,
        'DN6TH-ETXEN-Q3TD3' => 36,
        'W8WFF-3Q89T-YFQMG' => 37,
        'FNDG4-AYP4W-LDLQB' => 38,
        'Q88XE-6RHKD-DNPTD' => 39,
        'FHJ33-YL4FF-4TWYQ' => 40,
        'TH4PE-D4AC8-P4RYK' => 41,
        'BWBQB-GH8K8-NR4NG' => 42,
        '3KGJC-WEPL8-H3F9B' => 43,
        '8JJTX-B98P6-FDPEN' => 44,
        'WCW3D-GFM9Q-F3J9A' => 45,
        'T66LE-MTE3G-QHWQJ' => 46,
        'TE63E-JCQJX-FF9L4' => 47,
        'WTLCR-QQTXM-4EKB6' => 48,
        '99LCE-TCADD-PPK63' => 49,
        'PPRH3-YRLQJ-MERNA' => 50,
    ];

    static function getGroup($code)
    {
        if (isset(static::$codeMap[$code])) {
            return static::$codeMap[$code];
        } else {
            throw new Exception("Cannot find the code \"$code\"");
        }
    }

    static function getCode($group)
    {
        $groupMap = array_flip(static::$codeMap);

        if (isset($groupMap[$group])) {
            return $groupMap[$group];
        } else {
            throw new Exception("Cannot find the group \"$group\"");
        }
    }
}

class DashboardData
{
    static function fromCode($code)
    {
        $group = GroupCodes::getGroup($code);

        return (new static($group))->performances;
    }

    private $performances;

    public function __construct($group)
    {
        $this->group = $group;

        $this->performances = [];

        // Select all performances of the group
        $this->getPerformances();

        // Select all interventions of those performances
        $this->getInterventions();
    }

    private function getPerformances()
    {
        $query =
            'SELECT
                "generated_sequences"."seed",
                "generated_sequences"."time",
                "generated_sequences"."width",
                "generated_sequences"."height",
                "generated_sequences"."length",
                "performance"."id",
                "performance"."age",
                "performance"."user_id",
                "performance"."successes",
                "performance"."failures",
                "performance"."mistakes",
                "performance"."elapsed"
            FROM "performance"
            JOIN "generated_sequences"
                ON "generated_sequences"."id" = "performance"."sequence_id"
            WHERE "generated_sequences"."group" = :group';

        $result = Database::query($query, ['group' => $this->group]);

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $performance_id = $row['id'];
            unset($row['id']);

            $seed = $row['seed'];
            unset($row['seed']);

            $generator = new SequenceGenerator($row['width'], $row['height'], $row['length']);
            $sequence = $generator->generate($seed);

            $this->performances[$performance_id] = $row;
            $this->performances[$performance_id]['interactions'] = [];
            $this->performances[$performance_id]['sequence'] = $sequence;
        }
    }

    private function getInterventions()
    {
        $ids = implode(',', array_keys($this->performances));

        $query =
            'SELECT
                "performance_id",
                "x",
                "y",
                "distance",
                "elapsed",
                "type",
                "class"
            FROM "interactions"
            WHERE "performance_id" IN (' . $ids . ')
            ORDER BY "id"';

        $result = Database::query($query);

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $performance_id = $row['performance_id'];

            unset($row['performance_id']);

            $this->performances[$performance_id]['interactions'][] = $row;
        }
    }
}
