from flask import Flask, render_template, request
import mysql_db as connection
import os

ruta_raiz = "/opt/lampp/htdocs/wsdl_tablas" #url raiz del arbol que contiene los archivos
tabla = 'tablas_parametros' # tabla de la base de datos

app =  Flask(__name__)

@app.route('/')
def home():
    db = connection.connect()
    query = 'select curdate()'
    cursor = connection.executeQuery(db,query)
    date = cursor.fetchone()
    connection.disconnect(db)
    return "Hello! current date is {}.".format(date[0])

@app.route('/actualizar_tablas')
def actualizar_tablas():
    db = connection.connect()
    truncate = connection.executeUpdate(db,'TRUNCATE TABLE {}'.format(tabla))
    if truncate:
        print('Actualizando arbol de tablas en la bd')
        for dirpath, dirnames, filenames in os.walk(ruta_raiz):
            if filenames:                
                for file_name in filenames:                      
                    if file_name.find('.parametros') > 0 and file_name.find('.parametros') + len('.parametros') == len(file_name):
                        dirpath_encode = dirpath.encode('utf-8', 'surrogateescape').decode('utf-8', 'replace')
                        filepath_encode = file_name.encode('utf-8', 'surrogateescape').decode('utf-8', 'replace') 
                        query = 'INSERT INTO tablas_parametros (tabla_path) VALUES ("{}")'.format(dirpath_encode + '/' + filepath_encode)
                        connection.executeUpdate(db,query)
    connection.disconnect(db)
    return "Proceso de actualizacion de tablas realizado exitosamente."

@app.route('/path_tabla/', methods=['POST'])
def path_table():
    nombre_archivo = request.form['file']
    print('buscando archivo: {} ...'.format(nombre_archivo))
    if len(nombre_archivo) < 5:
        return "El nombre del archivo no es valido."
    db = connection.connect()
    cursor = connection.executeQuery(db,'SELECT tabla_path AS url FROM {} WHERE  tabla_path like "%{}"  order by length(tabla_path) asc LIMIT 1'.format(tabla, nombre_archivo)) 
    #cursor = connection.executeQuery(db,'SELECT tabla_path AS url FROM {} WHERE tabla_path like "%{}" LIMIT 1'.format(tabla, nombre_archivo))
    print('Ejecutando querie: {}'.format('SELECT tabla_path AS url FROM {} WHERE tabla_path like "%{}" LIMIT 1'.format(tabla, nombre_archivo)))
    data = cursor.fetchone()
    if data is None:
        return 'Archivo de tabla no existe'
    else:
        return data[0]

if __name__== '__main__':
    app.run(
        port = 8085,
        debug = True,
        host = '0.0.0.0' 
    )