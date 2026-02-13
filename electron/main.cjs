const { app, BrowserWindow } = require('electron')
const { execFile } = require('child_process')
const path = require('path')

let phpProcess

function startLaravel() {
    const phpPath = 'php'
    const publicPath = path.join(__dirname, '../public')

    phpProcess = execFile(phpPath, [
        '-S',
        '127.0.0.1:8000',
        '-t',
        publicPath
    ])
}

function createWindow() {
    const win = new BrowserWindow({
        width: 1200,
        height: 800,
        autoHideMenuBar: true,
        webPreferences: {
            contextIsolation: true
        }
    })

    win.loadURL('http://127.0.0.1:8000')
}

app.whenReady().then(() => {
    startLaravel()
    createWindow()
})

app.on('window-all-closed', () => {
    if (phpProcess) phpProcess.kill()
    app.quit()
})