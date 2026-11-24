<?php

namespace Classes;

use PHPMailer\PHPMailer\PHPMailer;

class Email
{
    protected $email;
    protected $nombre;
    protected $token;

    public function __construct($email, $nombre, $token)
    {
        $this->email = $email;
        $this->nombre = $nombre;
        $this->token = $token;
    }

    private function crearMailer(): PHPMailer
    {
        // true habilita excepciones para que LoginController capture caídas SMTP (RES-03)
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $_ENV['EMAIL_HOST'];
        $mail->SMTPAuth = true;
        $mail->Port = (int) $_ENV['EMAIL_PORT'];
        $mail->Username = $_ENV['EMAIL_USER'];
        $mail->Password = $_ENV['EMAIL_PASSWORD'];
        $mail->Timeout = 10;

        $remitente = $_ENV['EMAIL_FROM'] ?? 'cuentas@uptask.com';
        $mail->setFrom($remitente, 'UpTask');
        $mail->addAddress($this->email, $this->nombre);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        return $mail;
    }

    private function obtenerBaseUrl(): string
    {
        return rtrim($_ENV['APP_URL'] ?? 'http://localhost:3000', '/');
    }

    public function enviarConfirmacion(): void
    {
        $mail = $this->crearMailer();
        $mail->Subject = 'Confirma tu cuenta en UpTask';

        $nombreSeguro = htmlspecialchars($this->nombre, ENT_QUOTES, 'UTF-8');
        $enlace = $this->obtenerBaseUrl() . '/confirmar?token=' . urlencode($this->token);

        $contenido = '<html>';
        $contenido .= "<p><strong>Hola {$nombreSeguro}</strong>, has creado tu cuenta en UpTask. Solo debes confirmarla en el siguiente enlace:</p>";
        $contenido .= "<p>Presiona aquí: <a href='{$enlace}'>Confirmar Cuenta</a></p>";
        $contenido .= '<p>Este enlace expirará en 1 hora por seguridad.</p>';
        $contenido .= '<p>Si tú no creaste esta cuenta, puedes ignorar este mensaje.</p>';
        $contenido .= '</html>';

        $mail->Body = $contenido;
        $mail->send();
    }

    public function enviarInstrucciones(): void
    {
        $mail = $this->crearMailer();
        $mail->Subject = 'Restablece tu contraseña en UpTask';

        $nombreSeguro = htmlspecialchars($this->nombre, ENT_QUOTES, 'UTF-8');
        $enlace = $this->obtenerBaseUrl() . '/restablecer?token=' . urlencode($this->token);

        $contenido = '<html>';
        $contenido .= "<p><strong>Hola {$nombreSeguro}</strong>, has solicitado restablecer tu contraseña en UpTask. Sigue el siguiente enlace para crear una nueva:</p>";
        $contenido .= "<p>Presiona aquí: <a href='{$enlace}'>Restablecer Contraseña</a></p>";
        $contenido .= '<p>Este enlace expirará en 1 hora por seguridad.</p>';
        $contenido .= '<p>Si tú no solicitaste este cambio, puedes ignorar este mensaje.</p>';
        $contenido .= '</html>';

        $mail->Body = $contenido;
        $mail->send();
    }
}
