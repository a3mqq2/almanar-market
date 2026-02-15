const { app, BrowserWindow, dialog } = require('electron')
const { spawn } = require('child_process')
const path = require('path')
const fs = require('fs')
const net = require('net')

let phpProcess = null
let mainWindow = null
const PORT = 3000

function getProjectPath(relativePath = '') {
    if (app.isPackaged) {
        return path.join(process.resourcesPath, relativePath)
    }
    return path.join(__dirname, '..', relativePath)
}

function getPhpPath() {
    const xamppPhp = 'C:\\xampp\\php\\php.exe'

    if (fs.existsSync(xamppPhp)) {
        return xamppPhp
    }

    const bundledPhp = getProjectPath('php/php.exe')

    if (fs.existsSync(bundledPhp)) {
        return bundledPhp
    }

    return 'php'
}

function isPortAvailable(port) {
    return new Promise(resolve => {
        const server = net.createServer()

        server.once('error', () => resolve(false))

        server.once('listening', () => {
            server.close()
            resolve(true)
        })

        server.listen(port, '127.0.0.1')
    })
}

async function waitForServer(maxAttempts = 40) {
    for (let i = 0; i < maxAttempts; i++) {
        const available = await isPortAvailable(PORT)

        if (!available) {
            return true
        }

        await new Promise(r => setTimeout(r, 500))
    }

    return false
}

async function startLaravel() {
    const phpPath = getPhpPath()
    const artisanPath = getProjectPath('artisan')
    const appPath = getProjectPath()

    if (!fs.existsSync(artisanPath)) {
        dialog.showErrorBox('Error', 'artisan not found: ' + artisanPath)
        app.quit()
        return false
    }

    phpProcess = spawn(
        phpPath,
        ['artisan', 'serve', '--host=127.0.0.1', '--port=' + PORT],
        {
            cwd: appPath,
            env: process.env,
            windowsHide: true
        }
    )

    phpProcess.stdout.on('data', data => {
        console.log('PHP:', data.toString())
    })

    phpProcess.stderr.on('data', data => {
        console.error('PHP ERR:', data.toString())
    })

    phpProcess.on('error', err => {
        dialog.showErrorBox('PHP Error', err.message)
        app.quit()
    })

    return await waitForServer()
}

function createWindow() {
    mainWindow = new BrowserWindow({
        width: 1400,
        height: 900,
        minWidth: 1024,
        minHeight: 768,
        autoHideMenuBar: true,
        icon: path.join(__dirname, '../build/icon.ico'),
        webPreferences: {
            contextIsolation: true,
            nodeIntegration: false,
            devTools: !app.isPackaged
        }
    })

    mainWindow.loadURL('http://127.0.0.1:' + PORT)

    mainWindow.on('closed', () => {
        mainWindow = null
    })
}

function createLoadingWindow() {
    const loading = new BrowserWindow({
        width: 400,
        height: 300,
        frame: false,
        transparent: true,
        alwaysOnTop: true,
        webPreferences: {
            contextIsolation: true
        }
    })

    loading.loadFile(path.join(__dirname, 'loading.html'))

    return loading
}

app.whenReady().then(async () => {
    const loading = createLoadingWindow()

    const serverReady = await startLaravel()

    if (serverReady) {
        loading.close()
        createWindow()
    } else {
        loading.close()
        dialog.showErrorBox('Error', 'Server did not start')
        app.quit()
    }
})

app.on('window-all-closed', () => {
    if (phpProcess) {
        phpProcess.kill()
        phpProcess = null
    }

    app.quit()
})

app.on('before-quit', () => {
    if (phpProcess) {
        phpProcess.kill()
        phpProcess = null
    }
})

app.on('activate', () => {
    if (mainWindow === null && app.isReady()) {
        createWindow()
    }
})