" Specify the tags file.
" That file is an index of all classes, functions and variables etc.
" Use `make tags' to update it regularly.
"
" Some keyboard shortcuts in view mode:
" ^] -- go to class definition;
" g] -- go to definition, select from menu;
" ^t -- go back;
" [I -- show all occurences in the current file.
"
" Some keyboard shortcuts in editor mode:
" ^P -- autocomplete tag.
" ^N -- autocomplete tag.
set tags=.tags

" Default spacing.
set ts=4 sts=4 sw=4 et

au FileType php set ts=4 sts=4 sw=4 et tw=0 foldmethod=indent foldlevel=1
au FileType html.twig set ts=4 sts=4 sw=4 et tw=0

" Display line endings and other stuff.
" http://vim.wikia.com/wiki/Highlight_unwanted_spaces
set list listchars=tab:»·,trail:.,extends:>
nmap <F8> :set list!<CR>

" Folding setup.
set foldenable
" Close folds when cursor leaves them.
"set foldclose=all
"set foldcolumn=4
set foldnestmax=2
