<?php
/*

    可能的测试场景：

        - 不同的人（邮箱）用相同的密码不同的用户名注册，不会出现错误
        - 不同的人（邮箱）用相同的用户名密码注册，以用户名+密码登录会出现（多条数据错误）
        - 同一个人（邮箱）用相同的密码不同的用户名注册，以邮箱+密码登录会出现（多条数据错误）
        - 同一个人（邮箱）用相同的密码相同的用户名注册，应该被提示错误

    '100': 'Continue',
    '101': 'Switching Protocols',
    '102': 'Processing',
    '103': 'Early Hints',
    '200': 'OK',
    '201': 'Created',
    '202': 'Accepted',
    '203': 'Non-Authoritative Information',
    '204': 'No Content',
    '205': 'Reset Content',
    '206': 'Partial Content',
    '207': 'Multi-Status',
    '208': 'Already Reported',
    '226': 'IM Used',
    '300': 'Multiple Choices',
    '301': 'Moved Permanently',
    '302': 'Found',
    '303': 'See Other',
    '304': 'Not Modified',
    '305': 'Use Proxy',
    '307': 'Temporary Redirect',
    '308': 'Permanent Redirect',
    '400': 'Bad Request',
    '401': 'Unauthorized',
    '402': 'Payment Required',
    '403': 'Forbidden',
    '404': 'Not Found',
    '405': 'Method Not Allowed',
    '406': 'Not Acceptable',
    '407': 'Proxy Authentication Required',
    '408': 'Request Timeout',
    '409': 'Conflict',
    '410': 'Gone',
    '411': 'Length Required',
    '412': 'Precondition Failed',
    '413': 'Payload Too Large',
    '414': 'URI Too Long',
    '415': 'Unsupported Media Type',
    '416': 'Range Not Satisfiable',
    '417': 'Expectation Failed',
    '418': "I'm a Teapot",
    '421': 'Misdirected Request',
    '422': 'Unprocessable Entity',
    '423': 'Locked',
    '424': 'Failed Dependency',
    '425': 'Too Early',
    '426': 'Upgrade Required',
    '428': 'Precondition Required',
    '429': 'Too Many Requests',
    '431': 'Request Header Fields Too Large',
    '451': 'Unavailable For Legal Reasons',
    '500': 'Internal Server Error',
    '501': 'Not Implemented',
    '502': 'Bad Gateway',
    '503': 'Service Unavailable',
    '504': 'Gateway Timeout',
    '505': 'HTTP Version Not Supported',
    '506': 'Variant Also Negotiates',
    '507': 'Insufficient Storage',
    '508': 'Loop Detected',
    '509': 'Bandwidth Limit Exceeded',
    '510': 'Not Extended',
    '511': 'Network Authentication Required'

 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/lib/database.php';

// 创建 PDO 连接
$pdo = backend_create_pdo();

function vardmp($var){
    var_dump($var);
    echo("\n<br/><br/>");
}

function handle_login(PDO $pdo): array
{

    if (true) {
        
        $identifier = trim((string) ('luke.l.lin@hotmail.com'));
        $password = (string) ('F^UUrfA^x4YL');
        $pswd_hsh = password_hash($password, PASSWORD_DEFAULT);

        if (str_contains($identifier, '@')) {
            $statement = $pdo->prepare('SELECT id, username, email, password_hash, avatar_state, created_at, last_login_at FROM users WHERE email = :email');
            $statement->execute(['email' => $identifier]);
        } else {
            $statement = $pdo->prepare('SELECT id, username, email, password_hash, avatar_state, created_at, last_login_at FROM users WHERE username = :username');
            $statement->execute(['username' => $identifier]);
        }
        $users = $statement->fetchAll(PDO::FETCH_ASSOC);
        for ($i = 0; $i < count($users); $i++) {
            $uu = $users[$i];
            $pswdchk = password_verify($password, $users[$i]['password_hash']);
            echo $uu['username']." - ".$pswdchk."<br/>";
        }
    }
    else {

        if (count($users) !== 1 || !password_verify($password, $users[0]['password_hash'])) {
            return [
                'status' => 401,
                'body' => ['error' => 'Invalid credentials or ambiguous login.'],
            ];
        }

        $user = $users[0];

        $update = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $update->execute(['id' => $user['id']]);

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'message' => 'Login successful.',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'avatar_state' => $user['avatar_state'],
                    'created_at' => $user['created_at'],
                    'last_login_at' => $user['last_login_at'],
                ],
            ],
        ];
    }
    return [];
}

handle_login($pdo);
