import re
import os
from socket import gethostname
from paver.easy import *


def get_version():
    version_re = re.compile('^Version:\s(.*)$', re.M)
    version = version_re.search(path('src/rest.php').text())
    return version.group(1)

def copytree_ignore(directory, files):
    return 
    
    
options(
    develop=Bunch(
        wp_version='latest',
        html_root='~/Sites/wordprest_dev',
        url_root='http://%s/~%s/wordprest_dev' % 
            (gethostname(), os.environ.get('USER')),
        no_create_db=False,
        db_name='wordprest_dev',
        db_user='root',
        db_password='',
        db_host='localhost'
    )
)


@task
def build_docs(options):
    path('docs').rmtree()
    # Detect phpDocumentor.
    try:
        sh('which phpdoc')
    except BuildFailure:
        msg = 'You must install phpDocumentor to build documentation.'
        raise BuildFailure(msg)
    # Run phpDocumentor on src directory.
    try:
        sh('phpdoc -is -ti "WP reSt" -o HTML:frames:l0l33t -t docs -d src')
    except BuildFailure:
        msg = 'There was an error building the documentation.'
        raise BuildFailure(msg)


@task
@cmdopts([
    ('wp-version=',  'v', 'WordPress version to install.'),
    ('html-root=',   'r', 'The development site root path (Default: ~/Sites/wordprest_dev).'),
    ('url-root=',    'R', 'The development site URL root.'),
    ('no-create-db', 'C', 'Do not create the database (Default: False).'),
    ('db-name=',     'n', 'The name of the database to use.'),
    ('db-user=',     'u', 'The database username.'),
    ('db-password=', 'p', 'The database password.'),
    ('db-host=',     'H', 'The database hostname.'),
])
def develop(options):
    """
    Sets up the WP reSt development environment.
    
    WordPress is downloaded and installed according to the version specified
    by the WP_VERSION option. The version should be a "dotted major minor 
    revision" styled string (ex. "2.8.4") or the special case "latest", which
    will automatically grab the lastest stable version of WordPress.
    
    The development site will be set up at HTML_ROOT. If the path given is not
    an empty directory, an error will be thrown. HTML_ROOT should exist in a
    location under your web server document root (ex. "~/Sites/wordprest_dev/" on
    a Mac OS X system).
    
    Note: You *MUST* initialize and update the Git submodules before running
    this task (if the project uses git submodules).

    """
    import urllib
    import tarfile
    import tempfile
    
    # Normalize the HTML_ROOT.
    html_root = path(options.html_root).expand()
    
    info('Verifying HTML_ROOT: %s' % html_root)

    # Ensure that HTML_ROOT is writable and empty or nonexistant.    
    if html_root.exists():
        if not html_root.isdir():
            raise BuildFailure('A file exists at HTML_ROOT: %s' % html_root)
        if not html_root.access(os.W_OK):
            raise BuildFailure('Cannot write to HTML_ROOT: %s' % html_root)
        if not html_root.access(os.R_OK):
            raise BuildFailure('Cannot read HTML_ROOT: %s' % html_root)
        if html_root.listdir() is not None:
            raise BuildFailure('HTML_ROOT is not empty: %s' % html_root)

        # Remove the directory since copytree expects it to not exist.
        html_root.rmtree()
        
    else:
        # Make sure the HTML_ROOT parent exists.
        if not html_root.parent.exists():
            msg = 'Parent dir does not exist for HTML_ROOT: %s' % html_root
            raise BuildFailure(msg)
    
    # Check for write permission on HTML_ROOT parent.
    if not html_root.parent.access(os.W_OK):
        raise BuildFailure('Cannot create HTML_ROOT: %s' % html_root)
    
    # Determine WP download URL.
    wp_url = 'http://wordpress.org/'
    if options.wp_version == 'latest':
        wp_url += 'latest.tar.gz'
    else:
        wp_url += 'wordpress-%s.tar.gz' % options.wp_version
    
    # Download WP
    info('Downloading WordPress version %s from %s' % (options.wp_version, wp_url))
    (wp_tar_path, headers) = urllib.urlretrieve(wp_url)
    wp_tar = tarfile.open(wp_tar_path)    
    wp_tmp = path(tempfile.mkdtemp())
    
    # Extract WP
    info('Extracting WordPress source at %s' % wp_tmp)
    wp_tar.extractall(wp_tmp)
    wp_tmp = wp_tmp / 'wordpress'
    
    # Copy WP to HTML_ROOT
    info('Installing WordPress into HTML_ROOT: %s' % options.html_root)
    wp_tmp.copytree(html_root)
    
    # Create the database if necessary.
    if not options.no_create_db:
        info('Creating database %s' % options.db_name)   
        sh('/usr/local/mysql/bin/mysqladmin --host=%s --user=%s --password=%s create %s' % 
            (options.db_host, options.db_user, options.db_password, options.db_name))

    # Configure WP using the sample as a template.
    wp_conf_out = html_root / 'wp-config.php'
    info('Configuring WordPress settings file: %s' % wp_conf_out)
    wp_conf = path(html_root / 'wp-config-sample.php').text()
    wp_conf = wp_conf.replace('putyourdbnamehere', options.db_name)
    wp_conf = wp_conf.replace('usernamehere', options.db_user)
    wp_conf = wp_conf.replace('yourpasswordhere', options.db_password)
    wp_conf = wp_conf.replace('localhost', options.db_host)
    wp_conf_out.write_text(wp_conf)
    
    # Link plugin into WordPress.
    plugin_root = html_root / 'wp-content/plugins/wordprest'
    info('Linking plugin source into WordPress at %s' % plugin_root)
    src_root = path('src').expand().abspath()
    src_root.symlink(plugin_root)
    info('Launching development site: %s' % options.url_root)
    sh('open %s' % options.url_root)

@task
def release(options):
    from shutil import ignore_patterns
    from zipfile import ZipFile
    
    version = get_version()
    build_dir = path('build')
    src_root = path('src')
    build_dir.rmtree()
    build_dir.mkdir()
    package_dir = build_dir / 'wordprest'
    src_root.copytree(package_dir, 
                      ignore=ignore_patterns('.gitignore', '*.less', 'vendor'))
    archive_file = path('WordPreSt-%s.zip' % version)
    archive = ZipFile(archive_file, 'w')
    for filename in package_dir.walk():
        if not path(filename).isdir():
            archive.write(filename, filename.replace('build/', ''))
    archive.close()