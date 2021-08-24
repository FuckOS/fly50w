FLYC=../fly50w
INPUT=manifest.f5w
OUTPUT=./dist/main.f5wc

build:
	${FLYC} -c ${INPUT} -f -o ${OUTPUT}

all: build print_run_msg run

build_silent:
	${FLYC} -c ${INPUT} -f -o ${OUTPUT} > /dev/null

print_run_msg:
	@echo
	@echo "===== Running program ====="
	@echo

all_silent: build_silent run

run:
	@${FLYC} -r ${OUTPUT}
