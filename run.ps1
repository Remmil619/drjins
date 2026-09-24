$p1 = Start-Process php -WorkingDirectory ".\drjins-clinic-system" `
    -ArgumentList "artisan","serve","--port=8000" `
    -PassThru

$p2 = Start-Process php -WorkingDirectory ".\drjins-booking-portal" `
    -ArgumentList "artisan","serve","--port=8001" `
    -PassThru

Register-EngineEvent PowerShell.Exiting -Action {
    foreach ($id in @($p1.Id, $p2.Id)) {
        try {
            Stop-Process -Id $id -Force -ErrorAction SilentlyContinue
        } catch {}
    }
} | Out-Null

while ($true) {
    Start-Sleep 1
}