import zipfile
import os

zip_name = 'Deploy_Hostinger_V3.zip'
if os.path.exists(zip_name):
    os.remove(zip_name)

with zipfile.ZipFile(zip_name, 'w', zipfile.ZIP_DEFLATED) as zipf:
    for root, dirs, files in os.walk('.'):
        for file in files:
            file_path = os.path.join(root, file)
            # Remove './' from the beginning
            arcname = os.path.relpath(file_path, '.')
            
            # Skip unwanted files
            if arcname.startswith('.git') or arcname.startswith('Deploy_Hostinger') or arcname == 'build_zip.php' or arcname == 'build.py':
                continue
                
            # Convert backslashes to forward slashes for Linux compatibility
            arcname_linux = arcname.replace('\\', '/')
            
            # Swap db_hostinger.php to db.php
            if arcname_linux == 'config/db.php':
                continue
            elif arcname_linux == 'config/db_hostinger.php':
                zipf.write(file_path, 'config/db.php')
            else:
                zipf.write(file_path, arcname_linux)

print("ZIP created successfully with Python!")
