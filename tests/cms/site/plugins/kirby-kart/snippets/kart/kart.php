<?php
// KART localhost dev demo should not be used online
if (! kirby()->environment()->isLocal()) {
    go('/', 418);
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= $page->title() ?></title>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            max-width: 768px;
            margin: 0 auto;
            padding-bottom: 1rem;
            & > nav {
                display: flex;
                justify-content: space-between;
                border-bottom: 1px dotted #ccc;
                margin-bottom: 1rem;
                ul {
                    display: flex;
                    flex-wrap: wrap;
                    list-style: none;
                    padding: 0;
                    li:not(:last-child) {
                        margin-right: .5rem;
                        &::after {
                            content: '‣';
                            margin-left: .5rem;
                        }
                    }
                }
            }
            & > footer {
                border-top: 1px dotted #ccc;
                padding-top: 1rem;
                margin-top: 1rem;
                text-align: center;
                font-size: .8rem;
                a {
                    color: #999;
                }
            }
        }
        a, a:hover, a:visited, a:focus, a:active {
            color: #000;
            text-decoration: underline;
        }
        body[data-template="product"] {
            display: grid;
            grid-template-areas:
                "nav nav"
                "main aside"
                "footer footer";
            grid-template-columns: 2fr 1fr;
            gap: 1rem;
            nav {
                grid-area: nav;
                grid-column: span 2;
            }
            & > main {
                grid-area: main;
            }
            & > aside {
                grid-area: aside;
            }
            & > footer {
                grid-area: footer;
            }
            article img {
                max-width: 256px;
            }
        }
        body[data-template="cart"],
        body[data-template="signup"] {
            fieldset {
                max-width: 412px;
                margin: 0 auto;
            }
        }
        menu {
            margin: 0;
            padding: 0;
        }
        button {
            display: inline-block;
            background: #eee;
            border: 1px solid #ccc;
            cursor: pointer;
            padding: .5rem;
            margin: .25rem;
            border-radius: .25rem;
            min-width: 20ch;
            height: 2rem;
            &:hover {
                background: #fafafa;
            }
        }
        output {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            grid-gap: 2rem 1rem;
            article {
                border: 1px solid #ccc;
                border-radius: .25rem;
                padding: 1rem;
                &:hover {
                    border-color: #999;
                }
            }
        }
        article {
            img {
                border: none;
                width: 100%;
                aspect-ratio: 1;
                background-color: #999;
                margin-bottom: .5rem;
            }
        }
        fieldset {
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            background-color: #fafafa;
            border-radius: .25rem;
        }
        aside > fieldset > figure {
            padding: 0;
            margin: 0;
            img {
                border-radius: 50%;
                width: 3rem;
            }
            figcaption {
                margin: .5rem 0;
            }
        }
        aside fieldset menu {
            list-style: none;
        }
        aside fieldset menu li {
            border-top: 1px solid #ccc;
            padding: .5rem 0;
            &:first-child {
                border-top: none;
            }
            > div {
                display: flex;
                justify-content: flex-end;
                button {
                    min-width: 2rem;
                }
            }
        }
        legend {
            font-style: italic;
        }
        form {
            input {
                min-width: calc(100% - 1rem);
                padding: .5rem;
                border: 1px solid #ccc;
                border-radius: .25rem;
                margin-bottom: .5rem;
                &:focus {
                    outline: none;
                    border-color: #999;
                }
            }
            figure {
                margin: 0 0 .5rem;
                padding: 0;
                img {
                    width: 100%;
                    border: 1px solid #ccc;
                    aspect-ratio: 150/40;
                }

            }
        }
        search {
            border-bottom: 1px dotted #ccc;
            padding: 0 1rem 1rem;
            margin-bottom: 1rem;
            line-height: 1.6;
            .is-active {
                font-weight: bold;
            }
        }

        dialog {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            &::backdrop {
                background-color: rgba(0, 0, 0, .5) !important;
            }
        }
    </style>
</head>
<body data-template="<?= $page->template() ?>">
    <?php if ($msg = get('msg', kart()->message())) { ?>
        <dialog>
            <p><strong>Hi <?= kirby()->user()?->nameOrEmail() ?></strong>,</p>
            <p><?= strip_tags(urldecode((string) $msg)) ?></p>
            <form method="dialog">
                <button autofocus><?= t('close') ?></button>
            </form>
        </dialog>
        <script defer>
            // otherwise the backdrop will not work
            setTimeout(() => {
                document.querySelector('dialog').showModal();
            }, 100);
        </script>
    <?php } ?>
    <nav>
        <ul aria-label="Breadcrumb">
            <li><a href="<?= url('kart') ?>">Kart</a></li>
            <li><a href="<?= url('products') ?>">Products</a></li>
        </ul>
        <ul>
            <li><a href="<?= url('cart') ?>">Cart (<?= kart()->cart()->quantity() ?>)</a></li>
        </ul>
    </nav>

    <?= $slots->default() ?>

    <?php if (kirby()->plugin('bnomei/kart')->license()->status()->value() !== 'active') { ?>
    <footer>
        <a href="https://buy-kart.bnomei.com" target="_blank">Buy a KART license</a>
    </footer>
    <?php } ?>
</body>
</html>
